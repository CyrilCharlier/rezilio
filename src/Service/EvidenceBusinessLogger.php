<?php

namespace App\Service;

use App\Entity\Evidence;
use App\Entity\MeasureReview;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class EvidenceBusinessLogger
{
    public function __construct(
        private readonly LoggerInterface $businessRezilioLogger,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function logUploadSuccess(Evidence $evidence, ?User $initiator): void
    {
        $this->businessRezilioLogger->info('evidence_event', $this->buildEvidencePayload(
            'evidence.upload.success',
            $evidence,
            $initiator
        ));
    }

    public function logDeleteSuccess(Evidence $evidence, ?User $initiator): void
    {
        $this->businessRezilioLogger->info('evidence_event', $this->buildEvidencePayload(
            'evidence.delete.success',
            $evidence,
            $initiator
        ));
    }

    public function logDownloadSuccess(Evidence $evidence, ?User $initiator): void
    {
        $this->businessRezilioLogger->info('evidence_event', $this->buildEvidencePayload(
            'evidence.download.success',
            $evidence,
            $initiator
        ));
    }

    public function logAccessDenied(?MeasureReview $measureReview, ?User $initiator, string $reason): void
    {
        $this->businessRezilioLogger->warning('evidence_event', [
            'event_type' => 'evidence.access.denied',
            'evidence' => null,
            'context' => $this->buildRequestContext(),
            'meta' => array_merge(
                $this->buildInitiatorMeta($initiator),
                [
                    'reason' => $reason,
                    'measure_review' => [
                        'id' => $measureReview?->getId(),
                    ],
                    'measure' => $this->buildMeasureMeta($measureReview),
                ]
            ),
        ]);
    }

    public function logDeleteCsrfInvalid(Evidence $evidence, ?User $initiator): void
    {
        $this->businessRezilioLogger->warning('evidence_event', array_replace_recursive(
            $this->buildEvidencePayload('evidence.delete.csrf_invalid', $evidence, $initiator),
            [
                'meta' => [
                    'reason' => 'csrf_token_invalid',
                    'reason_code' => 'csrf_invalid',
                ],
            ]
        ));
    }

    public function logDownloadFileMissing(Evidence $evidence, ?User $initiator): void
    {
        $this->businessRezilioLogger->warning('evidence_event', array_replace_recursive(
            $this->buildEvidencePayload('evidence.download.file_missing', $evidence, $initiator),
            [
                'meta' => [
                    'reason' => 'Le fichier physique associé à la preuve est introuvable.',
                    'reason_code' => 'file_missing',
                ],
            ]
        ));
    }

    private function buildEvidencePayload(string $eventType, Evidence $evidence, ?User $initiator): array
    {
        $measureReview = method_exists($evidence, 'getMeasureReview') ? $evidence->getMeasureReview() : null;

        return [
            'event_type' => $eventType,
            'evidence' => [
                'id' => $evidence->getId(),
                'original_filename' => $evidence->getOriginalFilename(),
                'stored_filename' => method_exists($evidence, 'getStoredFilename') ? $evidence->getStoredFilename() : null,
                'mime_type' => method_exists($evidence, 'getMimeType') ? $evidence->getMimeType() : null,
                'file_size' => method_exists($evidence, 'getFileSize') ? $evidence->getFileSize() : null,
            ],
            'context' => $this->buildRequestContext(),
            'meta' => array_merge(
                $this->buildInitiatorMeta($initiator),
                [
                    'measure_review' => [
                        'id' => $measureReview?->getId(),
                    ],
                    'measure' => $this->buildMeasureMeta($measureReview),
                ]
            ),
        ];
    }

    private function buildRequestContext(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        return [
            'ip' => $request?->getClientIp(),
            'user_agent' => $request?->headers->get('User-Agent'),
            'route' => $request?->attributes->get('_route'),
            'method' => $request?->getMethod(),
        ];
    }

    private function buildInitiatorMeta(?User $initiator): array
    {
        return [
            'initiator' => [
                'id' => $initiator?->getId(),
                'username' => $initiator && method_exists($initiator, 'getUserIdentifier')
                    ? $initiator->getUserIdentifier()
                    : ($initiator && method_exists($initiator, 'getEmail') ? $initiator->getEmail() : null),
            ],
            'initiator_type' => 'user',
        ];
    }

    private function buildMeasureMeta(?MeasureReview $measureReview): array
    {
        $measure = $measureReview && method_exists($measureReview, 'getMeasure')
            ? $measureReview->getMeasure()
            : null;

        return [
            'id' => $measure?->getId(),
            'code' => $measure && method_exists($measure, 'getCode') ? $measure->getCode() : null,
            'label' => $measure && method_exists($measure, 'getLabel') ? $measure->getLabel() : null,
        ];
    }
}
