<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Service\Dashboard\DashboardMetricsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        #[CurrentUser] User $user,
        DashboardMetricsProvider $dashboardMetricsProvider,
    ): Response {
        $overview = $dashboardMetricsProvider->getOverview($user);

        return $this->render('dashboard/index.html.twig', [
            'overview' => $overview,
        ]);
    }
}
