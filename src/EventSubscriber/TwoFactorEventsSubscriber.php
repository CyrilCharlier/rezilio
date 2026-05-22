<?php

namespace App\EventSubscriber;

use App\Enum\EventType;
use App\Enum\LogType;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TwoFactorEventsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security_rezilio')]
        private readonly LoggerInterface $securityLogger,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::SUCCESS => 'onTwoFactorSuccess',
            TwoFactorAuthenticationEvents::FAILURE => 'onTwoFactorFailure',
            TwoFactorAuthenticationEvents::COMPLETE => 'onTwoFactorComplete',
        ];
    }

    public function onTwoFactorSuccess(TwoFactorAuthenticationEvent $event): void
    {
        $this->logTwoFactorEvent(EventType::TWOFA_CHALLENGE_SUCCESS, $event);
    }

    public function onTwoFactorFailure(TwoFactorAuthenticationEvent $event): void
    {
        $this->logTwoFactorEvent(EventType::TWOFA_CHALLENGE_FAILURE, $event, [
            'reason' => 'invalid_code',
        ]);
    }

    public function onTwoFactorComplete(TwoFactorAuthenticationEvent $event): void
    {
        // Optionnel : log spécifique quand TOUTE la 2FA est complétée
        $this->logTwoFactorEvent(EventType::TWOFA_CHALLENGE_SUCCESS, $event, [
            'reason' => 'two_factor_flow_complete',
        ]);
    }

    private function logTwoFactorEvent(EventType $eventType, TwoFactorAuthenticationEvent $event, array $extraMeta = []): void
    {
        $request = $event->getRequest();
        $token   = $event->getToken();
        $user    = $token->getUser();

        $session = $this->requestStack->getSession();
        $authFlowId = $session?->get('auth_flow_id');

        $this->securityLogger->info(LogType::AUTH_EVENT->value, [
            'event_type' => $eventType,
            'user' => [
                'id'       => is_object($user) && method_exists($user, 'getId') ? $user->getId() : null,
                'username' => is_object($user) && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
            ],
            'context' => [
                'ip'         => $this->getClientIp($request),
                'user_agent' => $request->headers->get('User-Agent'),
                '2fa_used'   => true,
                '2fa_method' => 'totp',
                'auth_flow_id' => $authFlowId,
            ],
            'meta' => array_merge([
                'initiator' => 'user',
            ], $extraMeta),
        ]);
    }

    private function getClientIp(?Request $request): ?string
    {
        return $request?->getClientIp();
    }
}
