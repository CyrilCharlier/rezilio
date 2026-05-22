<?php

namespace App\Service;

use App\Entity\Referential;
use App\Entity\User;
use App\Enum\EventType;
use App\Enum\LogType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

class ReferentialEventLogger
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.business_rezilio')]
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    public function logEvent(
        EventType $eventType,
        ?Referential $referential = null,
        array $meta = [],
        string $level = 'info',
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        $initiator = $this->security->getUser();

        $initiatorData = null;
        if ($initiator instanceof User) {
            $initiatorData = [
                'id' => $initiator->getId(),
                'username' => $initiator->getUserIdentifier(),
            ];
        }

        $payload = [
            'event_type' => $eventType->value,
            'referential' => [
                'id' => $referential?->getId(),
                'code' => $referential?->getCode(),
                'label' => $referential?->getLabel(),
            ],
            'context' => [
                'ip' => $request?->getClientIp(),
                'user_agent' => $request?->headers->get('User-Agent'),
                'route' => $request?->attributes->get('_route'),
                'method' => $request?->getMethod(),
            ],
            'meta' => array_merge([
                'initiator' => $initiatorData,
                'initiator_type' => $initiatorData ? 'user' : 'system',
                'reason' => null,
            ], $meta),
        ];

        $this->logger->log($level, LogType::REFERENTIAL_EVENT->value, $payload);
    }
}
