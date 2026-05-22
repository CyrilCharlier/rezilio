<?php

namespace App\Controller\Admin;

use App\Entity\Referential;
use App\Enum\EventType;
use App\Service\ReferentialEventLogger;
use App\Service\ReferentialExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/referential')]
class ReferentialExportController extends AbstractController
{
    public function __construct(
        private ReferentialExportService $referentialExportService,
        private ReferentialEventLogger $referentialEventLogger,
    ) {
    }

    #[Route('/{id}/export', name: 'admin_referential_export', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function export(Referential $ref): Response
    {
        try {
            $data = $this->referentialExportService->exportToArray($ref->getId());

            $json = json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );

            $referentialData = $data['referential'] ?? [];
            $filename = sprintf(
                'referential-%s-%s.json',
                $referentialData['code'] ?? $ref->getId(),
                date('Ymd-His')
            );

            $this->referentialEventLogger->logEvent(
                EventType::REFERENTIAL_EXPORT_SUCCESS,
                $ref,
                [
                    'category_count' => count($referentialData['categories'] ?? []),
                    'reason' => null,
                    'reason_code' => null,
                    'file_name' => $filename,
                    'file_size' => strlen($json),
                    'schema_version' => $data['schema_version'] ?? null,
                ],
                'info'
            );

            $response = new Response($json);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            $response->headers->set(
                'Content-Disposition',
                sprintf('attachment; filename="%s"', $filename)
            );

            return $response;
        } catch (\InvalidArgumentException $e) {
            $this->logWarningFailure(
                ref: $ref,
                reason: $e->getMessage(),
                reasonCode: $this->resolveBusinessReasonCode($e->getMessage()),
                exception: $e
            );

            $this->addFlash('danger', sprintf('Export impossible : %s', $e->getMessage()));

            return $this->redirectToRoute('app_referential_index');
        } catch (\JsonException $e) {
            $this->logTechnicalFailure(
                ref: $ref,
                publicReason: 'Erreur technique pendant la génération du fichier JSON.',
                reasonCode: 'json_encoding_failed',
                exception: $e
            );

            $this->addFlash('danger', 'Une erreur technique est survenue pendant l’export.');

            return $this->redirectToRoute('app_referential_index');
        } catch (\Throwable $e) {
            $this->logTechnicalFailure(
                ref: $ref,
                publicReason: 'Erreur technique pendant l’export.',
                reasonCode: 'unexpected_export_failure',
                exception: $e
            );

            $this->addFlash('danger', 'Une erreur technique est survenue pendant l’export.');

            return $this->redirectToRoute('app_referential_index');
        }
    }

    private function logWarningFailure(
        Referential $ref,
        string $reason,
        string $reasonCode,
        ?\Throwable $exception = null,
    ): void {
        $this->referentialEventLogger->logEvent(
            EventType::REFERENTIAL_EXPORT_FAILURE,
            $ref,
            [
                'referential_id' => $ref->getId(),
                'reason' => $reason,
                'reason_code' => $reasonCode,
                'exception_class' => $exception ? $exception::class : null,
            ],
            'warning'
        );
    }

    private function logTechnicalFailure(
        Referential $ref,
        string $publicReason,
        string $reasonCode,
        \Throwable $exception,
    ): void {
        $this->referentialEventLogger->logEvent(
            EventType::REFERENTIAL_EXPORT_FAILURE,
            $ref,
            [
                'referential_id' => $ref->getId(),
                'reason' => $publicReason,
                'reason_code' => $reasonCode,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ],
            'error'
        );
    }

    private function resolveBusinessReasonCode(string $message): string
    {
        return match (true) {
            str_contains($message, 'introuvable') => 'referential_not_found',
            str_contains($message, 'invalide') => 'invalid_export_payload',
            default => 'business_export_failed',
        };
    }
}
