<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Enum\EventType;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SecurityEventsSubscriber implements EventSubscriberInterface
{
    public const KEY_LOG = "auth_event";

    public function __construct(
        #[Autowire(service: 'monolog.logger.security_rezilio')]
        private LoggerInterface $securityLogger,
        private RequestStack $requestStack,
        private Security $security,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
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

        // Génération d'un ID de corrélation pour ce flow auth
        $session = $request?->getSession();
        $authFlowId = null;
        if ($session instanceof SessionInterface) {
            $authFlowId = Uuid::v4()->toRfc4122();
            $session->set('auth_flow_id', $authFlowId);
        }

        // 1) Logging + lastLoginAt comme tu le fais déjà
        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => EventType::LOGIN_SUCCESS,
            'user' => [
                'id'       => method_exists($user, 'getId') ? $user->getId() : null,
                'username' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
            ],
            'context' => [
                'ip'         => $request?->getClientIp(),
                'user_agent' => $request?->headers->get('User-Agent'),
                '2fa_used'   => false,
                '2fa_method' => null,
            ],
            'meta' => [
                'initiator' => 'user',
                'reason'    => null,
                'auth_flow_id' => $authFlowId,
            ],
        ]);

        $user->setLastLoginAt(new \DateTimeImmutable('now'));
        $this->em->flush();

        // 2) Si l'utilisateur n'a PAS de 2FA, on le force vers la page d'activation
        if ($user->getTotpSecret() === null) {
            $url = $this->router->generate('account_2fa_enable');
            $event->setResponse(new RedirectResponse($url));
        }
        // sinon, on laisse le flow normal continuer (le bundle 2FA gère la suite)
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $userIdentifier = $event->getPassport()?->getUser()->getUserIdentifier();
        $exception = $event->getException();

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => EventType::LOGIN_FAILURE,
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

        $session = $this->requestStack->getSession();
        $authFlowId = $session?->get('auth_flow_id');

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => EventType::LOGOUT,
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
                'auth_flow_id' => $authFlowId,
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
            'event_type' => EventType::LOGIN_REMEMBERED,
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
