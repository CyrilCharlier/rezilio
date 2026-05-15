<?php

namespace App\Controller;

use App\Entity\MeasureReview;
use App\Enum\MeasureReviewStatus;
use App\Form\MeasureReviewType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measure-review')]
final class MeasureReviewController extends AbstractController
{
    #[Route('/{id}/update-status', name: 'app_measure_review_update_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        MeasureReview $measureReview,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'measure_review_status_' . $measureReview->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide.');

            return $this->redirectToRoute('app_campaign_show', [
                'id' => $measureReview->getCampaign()->getId(),
            ]);
        }

        $submittedStatus = (string) $request->request->get('status', '');
        $status = MeasureReviewStatus::tryFrom($submittedStatus);

        if (!$status) {
            $this->addFlash('danger', 'Le statut sélectionné est invalide.');

            return $this->redirectToRoute('app_campaign_show', [
                'id' => $measureReview->getCampaign()->getId(),
            ]);
        }

        $measureReview->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Le statut de la revue a été mis à jour.');

        return $this->redirectToRoute('app_campaign_show', [
            'id' => $measureReview->getCampaign()->getId(),
        ]);
    }

    #[Route('/{id}/move', name: 'app_measure_review_move', methods: ['POST'])]
    public function move(
        Request $request,
        MeasureReview $measureReview,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->isCsrfTokenValid(
            'measure_review_status_' . $measureReview->getId(),
            (string) $request->request->get('_token')
        )) {
            return $this->json([
                'ok' => false,
                'message' => 'Le jeton CSRF est invalide.',
            ], 403);
        }

        $submittedStatus = (string) $request->request->get('status', '');
        $status = MeasureReviewStatus::tryFrom($submittedStatus);

        if (!$status) {
            return $this->json([
                'ok' => false,
                'message' => 'Le statut sélectionné est invalide.',
            ], 400);
        }

        $measureReview->setStatus($status);
        $entityManager->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Le statut de la revue a été mis à jour.',
            'reviewId' => $measureReview->getId(),
            'status' => $measureReview->getStatus()->value,
        ]);
    }

    #[Route('/{id}/modal', name: 'app_measure_review_modal', methods: ['GET'])]
    public function modal(MeasureReview $measureReview): Response
    {
        $form = $this->createForm(MeasureReviewType::class, $measureReview, [
            'action' => $this->generateUrl('app_measure_review_modal_save', [
                'id' => $measureReview->getId(),
            ]),
            'method' => 'POST',
        ]);

        return $this->render('measure_review/_modal_form.html.twig', [
            'measureReview' => $measureReview,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/modal/save', name: 'app_measure_review_modal_save', methods: ['POST'])]
    public function modalSave(
        Request $request,
        MeasureReview $measureReview,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $originalStatus = $measureReview->getStatus()->value;

        $form = $this->createForm(MeasureReviewType::class, $measureReview, [
            'action' => $this->generateUrl('app_measure_review_modal_save', [
                'id' => $measureReview->getId(),
            ]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->json([
                'ok' => false,
                'message' => 'Le formulaire n’a pas été soumis.',
            ], 400);
        }

        if (!$form->isValid()) {
            $html = $this->renderView('measure_review/_modal_form.html.twig', [
                'measureReview' => $measureReview,
                'form' => $form->createView(),
            ]);

            return $this->json([
                'ok' => false,
                'message' => 'Merci de corriger les erreurs du formulaire.',
                'html' => $html,
            ], 422);
        }

        $entityManager->flush();

        return $this->json([
            'ok' => true,
            'message' => 'La revue a été mise à jour.',
            'reviewId' => $measureReview->getId(),
            'oldStatus' => $originalStatus,
            'status' => $measureReview->getStatus()->value,
            'explanation' => $measureReview->getExplanation(),
            'proofUrl' => $measureReview->getProofUrl(),
            'notApplicableReason' => $measureReview->getNotApplicableReason(),
        ]);
    }
}
