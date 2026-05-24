<?php

namespace App\Controller;

use App\Entity\Evidence;
use App\Entity\MeasureReview;
use App\Form\EvidenceType;
use App\Repository\EvidenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\EvidenceBusinessLogger;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/measure-reviews')]
class EvidenceController extends AbstractController
{
    public function __construct(
        private readonly EvidenceBusinessLogger $evidenceBusinessLogger,
        private readonly Security $security,
    ) {
    }

    #[Route('/{id}/evidences', name: 'app_measure_review_evidences', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function panel(
        MeasureReview $measureReview,
        Request $request,
        EntityManagerInterface $entityManager,
        EvidenceRepository $evidenceRepository,
    ): Response {
        $this->denyAccessToMeasureReview($measureReview);

        $evidence = new Evidence();
        $evidence->setMeasureReview($measureReview);
        $evidence->setUploadedBy($this->getUser());

        $form = $this->createForm(EvidenceType::class, $evidence, [
            'action' => $this->generateUrl('app_measure_review_evidences', ['id' => $measureReview->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evidence);
            $entityManager->flush();

            $this->evidenceBusinessLogger->logUploadSuccess(
                $evidence,
                $this->security->getUser()
            );

            $this->addFlash('success', 'La preuve a été ajoutée.');

            return $this->render('evidence/panel.html.twig', [
                'measureReview' => $measureReview,
                'evidences' => $evidenceRepository->findByMeasureReview($measureReview),
                'form' => $this->createForm(EvidenceType::class, (new Evidence())
                    ->setMeasureReview($measureReview)
                    ->setUploadedBy($this->getUser()), [
                    'action' => $this->generateUrl('app_measure_review_evidences', ['id' => $measureReview->getId()]),
                    'method' => 'POST',
                ])->createView(),
            ]);
        }

        return $this->render('evidence/panel.html.twig', [
            'measureReview' => $measureReview,
            'evidences' => $evidenceRepository->findByMeasureReview($measureReview),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/evidences/{id}/download', name: 'app_evidence_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(Evidence $evidence): Response
    {
        $measureReview = $evidence->getMeasureReview();
        $this->denyAccessToMeasureReview($measureReview);

        $storedFilename = $evidence->getStoredFilename();
        $path = $this->getParameter('kernel.project_dir') . '/var/uploads/evidence/' . $storedFilename;

        if (!is_file($path)) {
            $this->evidenceBusinessLogger->logDownloadFileMissing(
                $evidence,
                $this->security->getUser()
            );

            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $this->evidenceBusinessLogger->logDownloadSuccess(
            $evidence,
            $this->security->getUser()
        );

        return $this->file(
            $path,
            $evidence->getOriginalFilename() ?? 'preuve',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }

    #[Route('/evidences/{id}', name: 'app_evidence_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Evidence $evidence,
        Request $request,
        EntityManagerInterface $entityManager,
        EvidenceRepository $evidenceRepository,
    ): Response {
        $measureReview = $evidence->getMeasureReview();
        $this->denyAccessToMeasureReview($measureReview);

        if (!$this->isCsrfTokenValid('delete_evidence_' . $evidence->getId(), (string) $request->request->get('_token'))) {
            $this->evidenceBusinessLogger->logDeleteCsrfInvalid(
                $evidence,
                $this->security->getUser()
            );

            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($evidence);
        $entityManager->flush();

        $this->evidenceBusinessLogger->logDeleteSuccess(
            $evidence,
            $this->security->getUser()
        );

        $this->addFlash('success', 'La preuve a été supprimée.');

        return $this->render('evidence/panel.html.twig', [
            'measureReview' => $measureReview,
            'evidences' => $evidenceRepository->findByMeasureReview($measureReview),
            'form' => $this->createForm(EvidenceType::class, (new Evidence())
                ->setMeasureReview($measureReview)
                ->setUploadedBy($this->getUser()), [
                'action' => $this->generateUrl('app_measure_review_evidences', ['id' => $measureReview?->getId()]),
                'method' => 'POST',
            ])->createView(),
        ]);
    }

    private function denyAccessToMeasureReview(?MeasureReview $measureReview): void
    {
        if (!$measureReview) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();

        if (!$user) {
            $this->evidenceBusinessLogger->logAccessDenied(
                $measureReview,
                null,
                'Utilisateur non authentifié.'
            );

            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (
            method_exists($measureReview, 'getCampaign')
            && $measureReview->getCampaign()
            && method_exists($measureReview->getCampaign(), 'getSociety')
            && method_exists($user, 'getUserSocieties')
        ) {
            $targetSociety = $measureReview->getCampaign()->getSociety();

            foreach ($user->getUserSocieties() as $userSociety) {
                if (method_exists($userSociety, 'getSociety') && $userSociety->getSociety() === $targetSociety) {
                    return;
                }
            }

            $this->evidenceBusinessLogger->logAccessDenied(
                $measureReview,
                $user,
                'L’utilisateur n’est pas rattaché à la société de la campagne.'
            );

            throw $this->createAccessDeniedException('Accès refusé à cette revue.');
        }

        $this->evidenceBusinessLogger->logAccessDenied(
            $measureReview,
            $user,
            'Contexte d’autorisation incomplet pour évaluer l’accès à la revue.'
        );

        throw $this->createAccessDeniedException('Accès refusé à cette revue.');
    }
}
