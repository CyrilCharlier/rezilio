<?php

namespace App\Service\Dashboard;

use App\Entity\Campaign;
use App\Entity\User;
use App\Enum\MeasureReviewStatus;
use App\Repository\CampaignRepository;
use App\Repository\MeasureNodeRepository;
use App\Repository\MeasureReviewRepository;
use App\Repository\RemediationActionRepository;

final class DashboardMetricsProvider
{
    public function __construct(
        private CampaignRepository $campaignRepository,
        private MeasureReviewRepository $measureReviewRepository,
        private MeasureNodeRepository $measureNodeRepository,
        private RemediationActionRepository $remediationActionRepository,
    ) {
    }

    public function getOverview(User $user, ?Campaign $campaign = null): array
    {
        $campaign ??= $this->resolveDefaultCampaign($user);

        if (!$campaign) {
            return $this->getEmptyOverview();
        }

        $reviews = $this->measureReviewRepository->findKanbanCardsByCampaign($campaign, []);
        $referential = $campaign->getReferential();
        $society = $campaign->getSociety();

        $totalCount = $referential
            ? $this->measureNodeRepository->countLeafNodesByReferential($referential)
            : count($reviews);

        $statusBreakdown = [
            MeasureReviewStatus::BLOCKED->value => 0,
            MeasureReviewStatus::NON_COMPLIANT->value => 0,
            MeasureReviewStatus::IN_PROGRESS->value => 0,
            MeasureReviewStatus::COMPLIANT->value => 0,
        ];

        $implementedCount = 0;

        foreach ($reviews as $review) {
            $status = $review->getStatus();

            $statusBreakdown[$status->value] = ($statusBreakdown[$status->value] ?? 0) + 1;

            if ($status === MeasureReviewStatus::COMPLIANT) {
                ++$implementedCount;
            }
        }

        $globalScore = $totalCount > 0
            ? round(($implementedCount / $totalCount) * 100, 1)
            : 0.0;

        $remediationMetrics = $this->remediationActionRepository->countDashboardMetrics([
            'campaign' => $campaign->getId(),
        ]);

        $nextDeadline = null;
        foreach ($this->remediationActionRepository->findForDashboard(['campaign' => $campaign->getId()]) as $action) {
            if (
                $action->getDueDate() !== null
                && !in_array($action->getStatus()?->value, ['done', 'cancelled'], true)
            ) {
                $nextDeadline = $action;
                break;
            }
        }

        return [
            'campaign' => $campaign,
            'referential' => $referential,
            'society' => $society,
            'globalScore' => $globalScore,
            'implementedCount' => $implementedCount,
            'totalCount' => $totalCount,
            'statusBreakdown' => $statusBreakdown,
            'remediationMetrics' => $remediationMetrics,
            'nextDeadline' => $nextDeadline,
            'weakestCategories' => array_slice($this->buildCategoryScores($reviews), 0, 5),
            'statusChart' => [
                [
                    'key' => MeasureReviewStatus::COMPLIANT->value,
                    'label' => 'Conformes',
                    'value' => $statusBreakdown[MeasureReviewStatus::COMPLIANT->value] ?? 0,
                    'color' => '#198754',
                ],
                [
                    'key' => MeasureReviewStatus::IN_PROGRESS->value,
                    'label' => 'En cours',
                    'value' => $statusBreakdown[MeasureReviewStatus::IN_PROGRESS->value] ?? 0,
                    'color' => '#f59f00',
                ],
                [
                    'key' => MeasureReviewStatus::NON_COMPLIANT->value,
                    'label' => 'Non conformes',
                    'value' => $statusBreakdown[MeasureReviewStatus::NON_COMPLIANT->value] ?? 0,
                    'color' => '#dc3545',
                ],
                [
                    'key' => MeasureReviewStatus::BLOCKED->value,
                    'label' => 'Bloquées',
                    'value' => $statusBreakdown[MeasureReviewStatus::BLOCKED->value] ?? 0,
                    'color' => '#6c757d',
                ],
            ],
        ];
    }

    private function buildCategoryScores(array $reviews): array
    {
        $categories = [];

        foreach ($reviews as $review) {
            $measure = $review->getMeasure();
            $category = $measure?->getCategory();

            if (!$category) {
                continue;
            }

            $categoryId = $category->getId();
            if (!$categoryId) {
                continue;
            }

            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $category->getName(),
                    'total' => 0,
                    'compliant' => 0,
                    'score' => 0.0,
                ];
            }

            ++$categories[$categoryId]['total'];

            if ($review->getStatus() === MeasureReviewStatus::COMPLIANT) {
                ++$categories[$categoryId]['compliant'];
            }
        }

        foreach ($categories as &$item) {
            $item['score'] = $item['total'] > 0
                ? round(($item['compliant'] / $item['total']) * 100, 1)
                : 0.0;
        }
        unset($item);

        usort($categories, static function (array $a, array $b): int {
            return [$a['score'], -$a['total'], $a['name']] <=> [$b['score'], -$b['total'], $b['name']];
        });

        return $categories;
    }

    private function resolveDefaultCampaign(User $user): ?Campaign
    {
        $campaigns = $this->campaignRepository->findAccessibleForUser($user, null);

        return $campaigns[0] ?? null;
    }

    private function getEmptyOverview(): array
    {
        return [
            'campaign' => null,
            'referential' => null,
            'society' => null,
            'globalScore' => 0.0,
            'implementedCount' => 0,
            'totalCount' => 0,
            'statusBreakdown' => [
                MeasureReviewStatus::BLOCKED->value => 0,
                MeasureReviewStatus::NON_COMPLIANT->value => 0,
                MeasureReviewStatus::IN_PROGRESS->value => 0,
                MeasureReviewStatus::COMPLIANT->value => 0,
            ],
            'remediationMetrics' => [
                'total' => 0,
                'draft' => 0,
                'open' => 0,
                'in_progress' => 0,
                'done' => 0,
                'cancelled' => 0,
                'overdue' => 0,
            ],
            'nextDeadline' => null,
            'weakestCategories' => [],
        ];
    }
}
