<?php

namespace App\Controller;

use App\Entity\MeasureReview;
use App\Entity\RemediationAction;
use App\Form\RemediationActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measure-reviews')]
final class RemediationActionModalController extends AbstractController
{
    #[Route('/{id}/remediation-actions/modal-list', name: 'app_remediation_action_list_modal', methods: ['GET'])]
    public function listModal(MeasureReview $measureReview): Response
    {
        return $this->render('campaign/remediation_action/_modal_list.html.twig', [
            'measureReview' => $measureReview,
            'remediationActions' => $measureReview->getRemediationActions(),
        ]);
    }

    #[Route('/{id}/remediation-actions/modal-new', name: 'app_remediation_action_new_modal', methods: ['GET', 'POST'])]
    public function newModal(Request $request, MeasureReview $measureReview, EntityManagerInterface $em): Response
    {
        $action = new RemediationAction();
        $action->setMeasureReview($measureReview);

        $form = $this->createForm(RemediationActionType::class, $action, [
            'action' => $this->generateUrl('app_remediation_action_new_modal', [
                'id' => $measureReview->getId(),
            ]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                return $this->json([
                    'ok' => false,
                    'html' => $this->renderView('campaign/remediation_action/_modal_form.html.twig', [
                        'form' => $form->createView(),
                        'measureReview' => $measureReview,
                        'action' => $action,
                    ]),
                    'message' => 'Merci de corriger les erreurs du formulaire.',
                ], 422);
            }

            $em->persist($action);
            $em->flush();

            return $this->json([
                'ok' => true,
                'html' => $this->renderView('campaign/remediation_action/_modal_list.html.twig', [
                    'measureReview' => $measureReview,
                    'remediationActions' => $measureReview->getRemediationActions(),
                ]),
                'message' => 'Action de remédiation créée.',
            ]);
        }

        return $this->render('campaign/remediation_action/_modal_form.html.twig', [
            'form' => $form->createView(),
            'measureReview' => $measureReview,
            'action' => $action,
        ]);
    }

    #[Route('/remediation-actions/{id}/modal-edit', name: 'app_remediation_action_edit_drawer', methods: ['GET', 'POST'])]
    public function editDrawer(Request $request, RemediationAction $action, EntityManagerInterface $em): Response
    {
        $measureReview = $action->getMeasureReview();

        $form = $this->createForm(RemediationActionType::class, $action, [
            'action' => $this->generateUrl('app_remediation_action_edit_drawer', [
                'id' => $action->getId(),
            ]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                return $this->json([
                    'ok' => false,
                    'html' => $this->renderView('campaign/remediation_action/_modal_form_drawer.html.twig', [
                        'form' => $form->createView(),
                        'measureReview' => $measureReview,
                        'action' => $action,
                    ]),
                    'message' => 'Merci de corriger les erreurs du formulaire.',
                ], 422);
            }

            $em->flush();

            return $this->json([
                'ok' => true,
                'html' => $this->renderView('campaign/remediation_action/_modal_list.html.twig', [
                    'measureReview' => $measureReview,
                    'remediationActions' => $measureReview->getRemediationActions(),
                ]),
                'message' => 'Action de remédiation mise à jour.',
            ]);
        }

        return $this->render('campaign/remediation_action/_modal_form_drawer.html.twig', [
            'form' => $form->createView(),
            'measureReview' => $measureReview,
            'action' => $action,
        ]);
    }

    #[Route('/remediation-actions/{id}/modal-edit', name: 'app_remediation_action_edit_modal', methods: ['GET', 'POST'])]
    public function editModal(Request $request, RemediationAction $action, EntityManagerInterface $em): Response
    {
        $measureReview = $action->getMeasureReview();

        $form = $this->createForm(RemediationActionType::class, $action, [
            'action' => $this->generateUrl('app_remediation_action_edit_modal', [
                'id' => $action->getId(),
            ]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                return $this->json([
                    'ok' => false,
                    'html' => $this->renderView('campaign/remediation_action/_modal_form.html.twig', [
                        'form' => $form->createView(),
                        'measureReview' => $measureReview,
                        'action' => $action,
                    ]),
                    'message' => 'Merci de corriger les erreurs du formulaire.',
                ], 422);
            }

            $em->flush();

            return $this->json([
                'ok' => true,
                'html' => $this->renderView('campaign/remediation_action/_modal_list.html.twig', [
                    'measureReview' => $measureReview,
                    'remediationActions' => $measureReview->getRemediationActions(),
                ]),
                'message' => 'Action de remédiation mise à jour.',
            ]);
        }

        return $this->render('campaign/remediation_action/_modal_form.html.twig', [
            'form' => $form->createView(),
            'measureReview' => $measureReview,
            'action' => $action,
        ]);
    }

    #[Route('/remediation-actions/{id}/delete', name: 'app_remediation_action_delete', methods: ['POST'])]
    public function delete(RemediationAction $action, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $measureReview = $action->getMeasureReview();

        if (!$this->isCsrfTokenValid('delete_remediation_action_'.$action->getId(), (string) $request->request->get('_token'))) {
            return $this->json([
                'ok' => false,
                'message' => 'Jeton CSRF invalide.',
            ], 400);
        }

        $em->remove($action);
        $em->flush();

        return $this->json([
            'ok' => true,
            'html' => $this->renderView('campaign/remediation_action/_modal_list.html.twig', [
                'measureReview' => $measureReview,
                'remediationActions' => $measureReview->getRemediationActions(),
            ]),
            'message' => 'Action de remédiation supprimée.',
        ]);
    }
}
