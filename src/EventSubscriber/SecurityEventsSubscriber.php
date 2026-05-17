<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Enum\AuthEventType;
use App\Enum\LogType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityEventsSubscriber implements EventSubscriberInterface
{
    public const KEY_LOG = "auth_event";

    public function __construct(
        #[Autowire(service: 'monolog.logger.security_rezilio')]
        private LoggerInterface $securityLogger,
        private RequestStack $requestStack,
        private Security $security,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class      => 'onLoginSuccess',
            LoginFailureEvent::class      => 'onLoginFailure',
            LogoutEvent::class            => 'onLogout',
            InteractiveLoginEvent::class  => 'onInteractiveLogin',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        /** @var User $user */
        $user = $event->getUser();

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => AuthEventType::LOGIN_SUCCESS,
            'user' => [
                'id'       => method_exists($user, 'getId') ? $user->getId() : null,
                'username' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
            ],
            'context' => [
                'ip'         => $request?->getClientIp(),
                'user_agent' => $request?->headers->get('User-Agent'),
                '2fa_used'   => false,   // à ajuster plus tard
                '2fa_method' => null,
            ],
            'meta' => [
                'initiator' => 'user',
                'reason'    => null,
            ],
        ]);

        $user->setLastLoginAt(new \DateTimeImmutable('now'));
        $this->em->flush();
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $userIdentifier = $event->getPassport()?->getUser()->getUserIdentifier();
        $exception = $event->getException();

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => AuthEventType::LOGIN_FAILURE,
            'user' => [
                'id'       => null,
                'username' => $userIdentifier,
            ],
            'context' => [
                'ip'         => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                '2fa_used'   => false,
                '2fa_method' => null,
            ],
            'meta' => [
                'initiator' => 'user',
                'reason'    => $exception?->getMessage(), // à simplifier plus tard (ex: bad_credentials)
            ],
        ]);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $token = $event->getToken();
        $user = $token?->getUser();

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => AuthEventType::LOGOUT,
            'user' => [
                'id'       => (is_object($user) && method_exists($user, 'getId')) ? $user->getId() : null,
                'username' => (is_object($user) && method_exists($user, 'getUserIdentifier')) ? $user->getUserIdentifier() : null,
            ],
            'context' => [
                'ip'         => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                '2fa_used'   => false,
                '2fa_method' => null,
            ],
            'meta' => [
                'initiator' => 'user',
                'reason'    => null,
            ],
        ]);
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        // On ne loggue ici que les connexions via remember-me
        if (!$this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $request = $event->getRequest();
        $token   = $event->getAuthenticationToken();
        $user    = $token->getUser();

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => AuthEventType::LOGIN_REMEMBERED,
            'user' => [
                'id'       => (is_object($user) && method_exists($user, 'getId')) ? $user->getId() : null,
                'username' => (is_object($user) && method_exists($user, 'getUserIdentifier')) ? $user->getUserIdentifier() : null,
            ],
            'context' => [
                'ip'         => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                '2fa_used'   => false,
                '2fa_method' => null,
            ],
            'meta' => [
                'initiator' => 'system',              // c’est le cookie qui rejoue la connexion
                'reason'    => 'remember_me_cookie',
            ],
        ]);
    }
}
