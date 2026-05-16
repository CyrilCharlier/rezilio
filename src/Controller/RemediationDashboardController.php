<?php

namespace App\Controller;

use App\Entity\RemediationAction;
use App\Enum\RemediationActionStatus;
use App\Repository\CampaignRepository;
use App\Repository\RemediationActionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/remediations', name: 'app_remediation_dashboard_')]
final class RemediationDashboardController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        RemediationActionRepository $remediationActionRepository,
        CampaignRepository $campaignRepository,
    ): Response {
        $filters = [
            'search' => trim((string) $request->query->get('search', '')),
            'status' => trim((string) $request->query->get('status', '')),
            'priority' => trim((string) $request->query->get('priority', '')),
            'campaign' => trim((string) $request->query->get('campaign', '')),
            'view' => trim((string) $request->query->get('view', 'kanban')),
        ];

        if (!in_array($filters['view'], ['kanban', 'table'], true)) {
            $filters['view'] = 'kanban';
        }

        $actions = $remediationActionRepository->findForDashboard($filters);
        $metrics = $remediationActionRepository->countDashboardMetrics($filters);
        $groupedActions = $remediationActionRepository->groupByStatus($actions);
        $campaigns = $campaignRepository->findBy([], ['name' => 'ASC']);

        return $this->render('remediation/index.html.twig', [
            'actions' => $actions,
            'groupedActions' => $groupedActions,
            'metrics' => $metrics,
            'filters' => $filters,
            'campaigns' => $campaigns,
            'statusUpdateCsrfToken' => $this->container->get('security.csrf.token_manager')->getToken('remediation_status')->getValue(),
        ]);
    }

    #[Route('/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        RemediationActionRepository $remediationActionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Requête invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $csrfToken = $request->headers->get('X-CSRF-Token', '');

        if (!$this->isCsrfTokenValid('remediation_status_update', $csrfToken)) {
            return $this->json(['ok' => false, 'message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $remediationAction = $remediationActionRepository->find($payload['id'] ?? null);

        if (!$remediationAction instanceof RemediationAction) {
            return $this->json(['ok' => false, 'message' => 'Action introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $status = RemediationActionStatus::tryFrom((string) ($payload['status'] ?? ''));

        if (!$status instanceof RemediationActionStatus) {
            return $this->json(['ok' => false, 'message' => 'Statut invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $remediationAction->setStatus($status);
        $entityManager->flush();

        return $this->json([
            'ok'        => true,
            'message'   => 'Statut mis à jour.',
            'updatedAt' => $remediationAction->getUpdatedAt()?->format('d/m/Y H:i'),
        ]);
    }
}
