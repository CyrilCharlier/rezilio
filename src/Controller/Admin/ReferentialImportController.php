<?php

namespace App\Controller\Admin;

use App\Enum\EventType;
use App\Service\ReferentialEventLogger;
use App\Service\ReferentialImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/referential')]
class ReferentialImportController extends AbstractController
{
    public function __construct(
        private ReferentialImportService $referentialImportService,
        private ReferentialEventLogger $referentialEventLogger,
    ) {
    }

    #[Route('/import', name: 'admin_referential_import', methods: ['POST'])]
    public function import(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('referential_import', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('referential_json');

        if (!$file instanceof UploadedFile) {
            $message = 'Aucun fichier sélectionné.';
            $this->addFlash('danger', $message);
            $this->logWarningFailure(
                reason: $message,
                reasonCode: 'no_file_uploaded',
                file: null
            );

            return $this->redirectToRoute('app_referential_index');
        }

        if (!$file->isValid()) {
            $message = 'Le fichier uploadé est invalide.';
            $this->addFlash('danger', $message);
            $this->logWarningFailure(
                reason: $message,
                reasonCode: 'uploaded_file_invalid',
                file: $file
            );

            return $this->redirectToRoute('app_referential_index');
        }

        if (($file->getSize() ?? 0) > 2 * 1024 * 1024) {
            $message = 'Le fichier dépasse la taille maximale autorisée (2 Mo).';
            $this->addFlash('danger', $message);
            $this->logWarningFailure(
                reason: $message,
                reasonCode: 'file_too_large',
                file: $file
            );

            return $this->redirectToRoute('app_referential_index');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ('json' !== $extension) {
            $message = 'Le fichier doit être au format JSON.';
            $this->addFlash('danger', $message);
            $this->logWarningFailure(
                reason: $message,
                reasonCode: 'invalid_file_extension',
                file: $file
            );

            return $this->redirectToRoute('app_referential_index');
        }

        try {
            $content = file_get_contents($file->getPathname());
            if (false === $content || '' === trim($content)) {
                throw new \RuntimeException('Le fichier est vide ou illisible.');
            }

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $referential = $this->referentialImportService->importFromArray($data);

            $this->referentialEventLogger->logEvent(
                EventType::REFERENTIAL_IMPORT_SUCCESS,
                $referential,
                [
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'schema_version' => $data['schema_version'] ?? null,
                ],
                'info'
            );

            $this->addFlash(
                'success',
                sprintf('Le référentiel "%s" a été importé avec succès.', $referential->getLabel())
            );
        } catch (\JsonException $e) {
            $this->logWarningFailure(
                reason: $e->getMessage(),
                reasonCode: 'invalid_json',
                file: $file,
                exception: $e
            );

            $this->addFlash('danger', sprintf('JSON invalide : %s', $e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            $this->logWarningFailure(
                reason: $e->getMessage(),
                reasonCode: $this->resolveBusinessReasonCode($e->getMessage()),
                file: $file,
                exception: $e
            );

            $this->addFlash('danger', sprintf('Import impossible : %s', $e->getMessage()));
        } catch (\Throwable $e) {
            $this->logTechnicalFailure(
                publicReason: 'Une erreur technique est survenue pendant l’import.',
                reasonCode: 'unexpected_import_failure',
                file: $file,
                exception: $e
            );

            $this->addFlash('danger', 'Une erreur technique est survenue pendant l’import.');
        }

        return $this->redirectToRoute('app_referential_index');
    }

    private function logWarningFailure(
        string $reason,
        string $reasonCode,
        ?UploadedFile $file,
        ?\Throwable $exception = null,
    ): void {
        $this->referentialEventLogger->logEvent(
            EventType::REFERENTIAL_IMPORT_FAILURE,
            null,
            [
                'file_name' => $file?->getClientOriginalName(),
                'file_size' => $file?->getSize(),
                'reason' => $reason,
                'reason_code' => $reasonCode,
                'exception_class' => $exception ? $exception::class : null,
            ],
            'warning'
        );
    }

    private function logTechnicalFailure(
        string $publicReason,
        string $reasonCode,
        ?UploadedFile $file,
        \Throwable $exception,
    ): void {
        $this->referentialEventLogger->logEvent(
            EventType::REFERENTIAL_IMPORT_FAILURE,
            null,
            [
                'file_name' => $file?->getClientOriginalName(),
                'file_size' => $file?->getSize(),
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
            str_contains($message, 'existe déjà') && str_contains($message, 'référentiel') => 'referential_code_already_exists',
            str_contains($message, 'existe déjà') && str_contains($message, 'code node') => 'node_code_already_exists',
            str_contains($message, 'existe déjà') && str_contains($message, 'nom de catégorie') => 'category_name_already_exists',
            str_contains($message, 'parent_id') => 'invalid_parent_reference',
            str_contains($message, 'Cycle détecté') => 'node_cycle_detected',
            str_contains($message, 'Champ "') => 'invalid_payload_field',
            default => 'business_validation_failed',
        };
    }
}
