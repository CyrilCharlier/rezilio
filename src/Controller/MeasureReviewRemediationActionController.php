<?php

namespace App\Controller\Campaign;

use App\Entity\MeasureReview;
use App\Entity\RemediationAction;
use App\Form\RemediationActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/campaign/measure-reviews/{id}/remediation-actions', name: 'app_measure_review_remediation_action_')]
final class MeasureReviewRemediationActionController extends AbstractController
{
    #[Route('/drawer', name: 'drawer', methods: ['GET'])]
    public function drawer(MeasureReview $measureReview): Response
    {
        return $this->render('campaign/measure_review/_remediation_drawer.html.twig', [
            'measureReview' => $measureReview,
            'remediationActions' => $measureReview->getRemediationActions(),
            'form' => null,
            'editingAction' => null,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, MeasureReview $measureReview, EntityManagerInterface $em): Response
    {
        $remediationAction = new RemediationAction();
        $remediationAction->setMeasureReview($measureReview);

        $form = $this->createForm(RemediationActionType::class, $remediationAction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($remediationAction);
            $em->flush();

            return $this->render('campaign/measure_review/_remediation_drawer.html.twig', [
                'measureReview' => $measureReview,
                'remediationActions' => $measureReview->getRemediationActions(),
                'form' => null,
                'editingAction' => null,
            ]);
        }

        return $this->render('campaign/measure_review/_remediation_drawer.html.twig', [
            'measureReview' => $measureReview,
            'remediationActions' => $measureReview->getRemediationActions(),
            'form' => $form->createView(),
            'editingAction' => null,
        ]);
    }

    #[Route('/{remediationAction}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MeasureReview $measureReview, RemediationAction $remediationAction, EntityManagerInterface $em): Response
    {
        if ($remediationAction->getMeasureReview()?->getId() !== $measureReview->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(RemediationActionType::class, $remediationAction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->render('campaign/measure_review/_remediation_drawer.html.twig', [
                'measureReview' => $measureReview,
                'remediationActions' => $measureReview->getRemediationActions(),
                'form' => null,
                'editingAction' => null,
            ]);
        }

        return $this->render('campaign/measure_review/_remediation_drawer.html.twig', [
            'measureReview' => $measureReview,
            'remediationActions' => $measureReview->getRemediationActions(),
            'form' => $form->createView(),
            'editingAction' => $remediationAction,
        ]);
    }

    #[Route('/{remediationAction}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, MeasureReview $measureReview, RemediationAction $remediationAction, EntityManagerInterface $em): Response
    {
        if ($remediationAction->getMeasureReview()?->getId() !== $measureReview->getId()) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_remediation_action_'.$remediationAction->getId(), (string) $request->request->get('_token'))) {
            $em->remove($remediationAction);
            $em->flush();
        }

        return $this->render('campaign/measure_review/_remediation_drawer.html.twig', [
            'measureReview' => $measureReview,
            'remediationActions' => $measureReview->getRemediationActions(),
            'form' => null,
            'editingAction' => null,
        ]);
    }
}
