<?php

namespace App\Controller\Admin;

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
    ) {}

    #[Route('/import', name: 'admin_referential_import', methods: ['POST'])]
    public function import(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('referential_import', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('referential_json');

        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_referential_index');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ('json' !== $extension) {
            $this->addFlash('error', 'Le fichier doit être au format JSON.');
            return $this->redirectToRoute('app_referential_index');
        }

        try {
            $content = file_get_contents($file->getPathname());
            if (false === $content || '' === trim($content)) {
                throw new \RuntimeException('Le fichier est vide ou illisible.');
            }

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $referential = $this->referentialImportService->importFromArray($data);

            $this->addFlash(
                'success',
                sprintf('Le référentiel "%s" a été importé avec succès.', $referential->getLabel())
            );
        } catch (\JsonException $e) {
            $this->addFlash('error', sprintf('JSON invalide : %s', $e->getMessage()));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Import impossible : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('app_referential_index');
    }
}