<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\User;
use App\Repository\CampaignRepository;
use App\Service\Dashboard\DashboardMetricsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        Request $request,
        #[CurrentUser] User $user,
        CampaignRepository $campaignRepository,
        DashboardMetricsProvider $dashboardMetricsProvider,
    ): Response {
        $campaigns = $campaignRepository->findAccessibleForUser($user, null);
        $selectedCampaign = $this->resolveSelectedCampaign($campaigns, $request->query->get('campaign'));

        $overview = $dashboardMetricsProvider->getOverview($user, $selectedCampaign);

        return $this->render('dashboard/index.html.twig', [
            'overview' => $overview,
            'campaigns' => $campaigns,
            'selectedCampaign' => $selectedCampaign,
        ]);
    }

    /**
     * @param Campaign[] $campaigns
     */
    private function resolveSelectedCampaign(array $campaigns, mixed $campaignId): ?Campaign
    {
        if ($campaignId !== null && ctype_digit((string) $campaignId)) {
            foreach ($campaigns as $campaign) {
                if ($campaign->getId() === (int) $campaignId) {
                    return $campaign;
                }
            }
        }

        return $campaigns[0] ?? null;
    }
}
