<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Entity\Campaign;
use App\Entity\Category;
use App\Entity\MeasureNode;
use App\Entity\MeasureReview;
use App\Entity\Referential;
use App\Entity\Society;
use App\Entity\User;
use App\Enum\MeasureReviewStatus;
use App\Repository\CampaignRepository;
use App\Repository\MeasureNodeRepository;
use App\Repository\MeasureReviewRepository;
use App\Repository\RemediationActionRepository;
use App\Service\Dashboard\DashboardMetricsProvider;
use PHPUnit\Framework\TestCase;
use App\Tests\Support\ReflectionTestTrait;
use ReflectionClass;

class DashboardMetricsProviderTest extends TestCase
{
    use ReflectionTestTrait;
    /**
     * Helper de test pour simuler une propriété privée Doctrine,
     * par exemple un "id" normalement généré par la base.
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($object, $value);
    }

    /**
     * Cas 1 : aucune campagne accessible pour l'utilisateur.
     *
     * On vérifie que le provider retourne bien l'overview vide défini
     * par la méthode getEmptyOverview().
     */
    public function testGetOverviewReturnsEmptyOverviewWhenNoCampaignIsAvailable(): void
    {
        $campaignRepository = $this->createMock(CampaignRepository::class);
        $measureReviewRepository = $this->createMock(MeasureReviewRepository::class);
        $measureNodeRepository = $this->createMock(MeasureNodeRepository::class);
        $remediationActionRepository = $this->createMock(RemediationActionRepository::class);

        $user = $this->createMock(User::class);

        // Aucune campagne accessible => overview vide.
        $campaignRepository
            ->expects($this->once())
            ->method('findAccessibleForUser')
            ->with($user, null)
            ->willReturn([]);

        // Aucun autre repository ne doit être sollicité.
        $measureReviewRepository->expects($this->never())->method($this->anything());
        $measureNodeRepository->expects($this->never())->method($this->anything());
        $remediationActionRepository->expects($this->never())->method($this->anything());

        $provider = new DashboardMetricsProvider(
            $campaignRepository,
            $measureReviewRepository,
            $measureNodeRepository,
            $remediationActionRepository
        );

        $overview = $provider->getOverview($user);

        $this->assertNull($overview['campaign']);
        $this->assertNull($overview['referential']);
        $this->assertNull($overview['society']);
        $this->assertSame(0.0, $overview['globalScore']);
        $this->assertSame(0, $overview['implementedCount']);
        $this->assertSame(0, $overview['totalCount']);
        $this->assertSame(0, $overview['reviewedCount']);
        $this->assertSame(0.0, $overview['coverageRate']);
        $this->assertSame(0, $overview['nonCompliantCount']);
        $this->assertSame(0, $overview['inProgressCount']);
        $this->assertSame(0, $overview['blockedCount']);
        $this->assertNull($overview['nextDeadline']);
        $this->assertSame([], $overview['weakestCategories']);
        $this->assertSame([], $overview['priorityActions']);

        $this->assertSame(
            [
                MeasureReviewStatus::BLOCKED->value => 0,
                MeasureReviewStatus::NON_COMPLIANT->value => 0,
                MeasureReviewStatus::IN_PROGRESS->value => 0,
                MeasureReviewStatus::COMPLIANT->value => 0,
            ],
            $overview['statusBreakdown']
        );

        $this->assertSame(
            [
                'total' => 0,
                'draft' => 0,
                'open' => 0,
                'in_progress' => 0,
                'done' => 0,
                'cancelled' => 0,
                'overdue' => 0,
            ],
            $overview['remediationMetrics']
        );
    }

    /**
     * Cas 2 : calcul des métriques principales sur une campagne existante.
     *
     * On couvre :
     * - le comptage des reviews ;
     * - le breakdown par statut ;
     * - le score global ;
     * - le taux de couverture.
     */
    public function testGetOverviewComputesMainMetrics(): void
    {
        $campaignRepository = $this->createMock(CampaignRepository::class);
        $measureReviewRepository = $this->createMock(MeasureReviewRepository::class);
        $measureNodeRepository = $this->createMock(MeasureNodeRepository::class);
        $remediationActionRepository = $this->createMock(RemediationActionRepository::class);

        $user = $this->createMock(User::class);
        $campaign = $this->createMock(Campaign::class);
        $referential = $this->createMock(Referential::class);
        $society = $this->createMock(Society::class);

        $campaign->method('getReferential')->willReturn($referential);
        $campaign->method('getSociety')->willReturn($society);
        $campaign->method('getId')->willReturn(42);

        $campaignRepository
            ->expects($this->once())
            ->method('findAccessibleForUser')
            ->with($user, null)
            ->willReturn([$campaign]);

        $reviewCompliant = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::COMPLIANT,
            'getMeasure' => null,
        ]);

        $reviewInProgress = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::IN_PROGRESS,
            'getMeasure' => null,
        ]);

        $reviewNonCompliant = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::NON_COMPLIANT,
            'getMeasure' => null,
        ]);

        $measureReviewRepository
            ->expects($this->once())
            ->method('findKanbanCardsByCampaign')
            ->with($campaign, [])
            ->willReturn([$reviewCompliant, $reviewInProgress, $reviewNonCompliant]);

        // 5 mesures dans le référentiel, 3 reviews faites.
        $measureNodeRepository
            ->expects($this->once())
            ->method('countLeafNodesByReferential')
            ->with($referential)
            ->willReturn(5);

        $remediationActionRepository
            ->expects($this->once())
            ->method('countDashboardMetrics')
            ->with(['campaign' => 42])
            ->willReturn([
                'total' => 2,
                'draft' => 0,
                'open' => 1,
                'in_progress' => 1,
                'done' => 0,
                'cancelled' => 0,
                'overdue' => 0,
            ]);

        // 1er appel : nextDeadline
        // 2e appel : priorityActions
        $remediationActionRepository
            ->expects($this->exactly(2))
            ->method('findForDashboard')
            ->with(['campaign' => 42])
            ->willReturn([]);

        $provider = new DashboardMetricsProvider(
            $campaignRepository,
            $measureReviewRepository,
            $measureNodeRepository,
            $remediationActionRepository
        );

        $overview = $provider->getOverview($user);

        $this->assertSame($campaign, $overview['campaign']);
        $this->assertSame($referential, $overview['referential']);
        $this->assertSame($society, $overview['society']);

        // 1 review conforme sur 5 mesures totales => 20.0%
        $this->assertSame(20.0, $overview['globalScore']);
        $this->assertSame(1, $overview['implementedCount']);
        $this->assertSame(5, $overview['totalCount']);

        // 3 reviews sur 5 mesures => 60.0% de couverture
        $this->assertSame(3, $overview['reviewedCount']);
        $this->assertSame(60.0, $overview['coverageRate']);

        $this->assertSame(1, $overview['nonCompliantCount']);
        $this->assertSame(1, $overview['inProgressCount']);
        $this->assertSame(0, $overview['blockedCount']);

        $this->assertSame(
            [
                MeasureReviewStatus::BLOCKED->value => 0,
                MeasureReviewStatus::NON_COMPLIANT->value => 1,
                MeasureReviewStatus::IN_PROGRESS->value => 1,
                MeasureReviewStatus::COMPLIANT->value => 1,
            ],
            $overview['statusBreakdown']
        );

        $this->assertCount(4, $overview['statusChart']);
        $this->assertSame('COMPLIANT', $overview['statusChart'][0]['key']);
        $this->assertSame('Conformes', $overview['statusChart'][0]['label']);
        $this->assertSame(1, $overview['statusChart'][0]['value']);
        $this->assertSame('#198754', $overview['statusChart'][0]['color']);
    }

    /**
     * Cas 3 : calcul des catégories faibles.
     *
     * On vérifie ici que les catégories sont :
     * - agrégées par catégorie ;
     * - scorées ;
     * - enrichies avec tone/toneLabel/color ;
     * - triées du plus faible score au plus fort.
     */
    public function testGetOverviewBuildsWeakestCategories(): void
    {
        $campaignRepository = $this->createMock(CampaignRepository::class);
        $measureReviewRepository = $this->createMock(MeasureReviewRepository::class);
        $measureNodeRepository = $this->createMock(MeasureNodeRepository::class);
        $remediationActionRepository = $this->createMock(RemediationActionRepository::class);

        $user = $this->createMock(User::class);
        $campaign = $this->createMock(Campaign::class);
        $referential = $this->createMock(Referential::class);
        $society = $this->createMock(Society::class);

        $campaign->method('getReferential')->willReturn($referential);
        $campaign->method('getSociety')->willReturn($society);
        $campaign->method('getId')->willReturn(42);

        $campaignRepository
            ->expects($this->once())
            ->method('findAccessibleForUser')
            ->with($user, null)
            ->willReturn([$campaign]);

        $measureNodeRepository
            ->expects($this->once())
            ->method('countLeafNodesByReferential')
            ->with($referential)
            ->willReturn(6);

        $remediationActionRepository
            ->expects($this->once())
            ->method('countDashboardMetrics')
            ->willReturn([
                'total' => 0,
                'draft' => 0,
                'open' => 0,
                'in_progress' => 0,
                'done' => 0,
                'cancelled' => 0,
                'overdue' => 0,
            ]);

        $remediationActionRepository
            ->expects($this->exactly(2))
            ->method('findForDashboard')
            ->willReturn([]);

        $governance = new Category();
        $governance->setName('Gouvernance');
        $this->setPrivateProperty($governance, 'id', 10);

        $protection = new Category();
        $protection->setName('Protection');
        $this->setPrivateProperty($protection, 'id', 20);

        $detection = new Category();
        $detection->setName('Détection');
        $this->setPrivateProperty($detection, 'id', 30);

        $node1 = (new MeasureNode())->setCategory($governance);
        $node2 = (new MeasureNode())->setCategory($governance);
        $node3 = (new MeasureNode())->setCategory($protection);
        $node4 = (new MeasureNode())->setCategory($protection);
        $node5 = (new MeasureNode())->setCategory($detection);
        $node6 = (new MeasureNode())->setCategory($detection);

        $review1 = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::NON_COMPLIANT,
            'getMeasure' => $node1,
        ]);
        $review2 = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::BLOCKED,
            'getMeasure' => $node2,
        ]);
        $review3 = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::COMPLIANT,
            'getMeasure' => $node3,
        ]);
        $review4 = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::IN_PROGRESS,
            'getMeasure' => $node4,
        ]);
        $review5 = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::COMPLIANT,
            'getMeasure' => $node5,
        ]);
        $review6 = $this->createConfiguredMock(MeasureReview::class, [
            'getStatus' => MeasureReviewStatus::COMPLIANT,
            'getMeasure' => $node6,
        ]);

        $measureReviewRepository
            ->expects($this->once())
            ->method('findKanbanCardsByCampaign')
            ->with($campaign, [])
            ->willReturn([
                $review1,
                $review2,
                $review3,
                $review4,
                $review5,
                $review6,
            ]);

        $provider = new DashboardMetricsProvider(
            $campaignRepository,
            $measureReviewRepository,
            $measureNodeRepository,
            $remediationActionRepository
        );

        $overview = $provider->getOverview($user);

        $this->assertCount(3, $overview['weakestCategories']);

        $this->assertSame(10, $overview['weakestCategories'][0]['id']);
        $this->assertSame('Gouvernance', $overview['weakestCategories'][0]['name']);
        $this->assertSame(2, $overview['weakestCategories'][0]['total']);
        $this->assertSame(0, $overview['weakestCategories'][0]['compliant']);
        $this->assertSame(0.0, $overview['weakestCategories'][0]['score']);
        $this->assertSame('danger', $overview['weakestCategories'][0]['tone']);
        $this->assertSame('Critique', $overview['weakestCategories'][0]['toneLabel']);
        $this->assertSame('#dc3545', $overview['weakestCategories'][0]['color']);

        $this->assertSame(20, $overview['weakestCategories'][1]['id']);
        $this->assertSame('Protection', $overview['weakestCategories'][1]['name']);
        $this->assertSame(2, $overview['weakestCategories'][1]['total']);
        $this->assertSame(1, $overview['weakestCategories'][1]['compliant']);
        $this->assertSame(50.0, $overview['weakestCategories'][1]['score']);
        $this->assertSame('warning', $overview['weakestCategories'][1]['tone']);
        $this->assertSame('À renforcer', $overview['weakestCategories'][1]['toneLabel']);
        $this->assertSame('#f59f00', $overview['weakestCategories'][1]['color']);

        $this->assertSame(30, $overview['weakestCategories'][2]['id']);
        $this->assertSame('Détection', $overview['weakestCategories'][2]['name']);
        $this->assertSame(2, $overview['weakestCategories'][2]['total']);
        $this->assertSame(2, $overview['weakestCategories'][2]['compliant']);
        $this->assertSame(100.0, $overview['weakestCategories'][2]['score']);
        $this->assertSame('success', $overview['weakestCategories'][2]['tone']);
        $this->assertSame('Maîtrisé', $overview['weakestCategories'][2]['toneLabel']);
        $this->assertSame('#198754', $overview['weakestCategories'][2]['color']);
    }
}
