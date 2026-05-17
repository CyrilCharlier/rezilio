<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\AuthEventType;
use App\Enum\LogType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

class UserSecurityLogger
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security_rezilio')]
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    public function logAccountEvent(
        AuthEventType $eventType,
        ?int $userId,
        ?string $username,
        array $meta = []
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        $initiator = $this->security->getUser();

        $initiatorData = null;
        if ($initiator instanceof User) {
            $initiatorData = [
                'id'       => $initiator->getId(),
                'username' => $initiator->getUserIdentifier(),
            ];
        }

        $this->logger->info(LogType::AUTH_EVENT->value, [
            'event_type' => $eventType->value,
            'user' => [
                'id'       => $userId,
                'username' => $username,
            ],
            'context' => [
                'ip'         => $request?->getClientIp(),
                'user_agent' => $request?->headers->get('User-Agent'),
                '2fa_used'   => false,
                '2fa_method' => null,
            ],
            'meta' => array_merge([
                'initiator' => $initiatorData,
                'initiator_type' => $initiatorData ? 'user' : 'system',
                'reason'    => null,
            ], $meta),
        ]);
    }
}
