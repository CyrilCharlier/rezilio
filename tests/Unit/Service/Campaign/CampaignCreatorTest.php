<?php

namespace App\Tests\Unit\Service\Campaign;

use App\Entity\Campaign;
use App\Entity\MeasureNode;
use App\Entity\MeasureReview;
use App\Entity\Referential;
use App\Entity\Society;
use App\Enum\MeasureReviewStatus;
use App\Form\Model\CampaignCreationModel;
use App\Repository\MeasureNodeRepository;
use App\Service\Campaign\CampaignCreator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la classe CampaignCreator.
 *
 * On isole complètement le service :
 *  - EntityManagerInterface est mocké (on ne touche pas à la vraie BDD)
 *  - MeasureNodeRepository est mocké (on contrôle précisément ce qu'il renvoie)
 */
class CampaignCreatorTest extends TestCase
{
    /**
     * Cas 1 : le modèle passé à createFromModel est incomplet.
     *
     * Objectif :
     *  - Vérifier qu'une InvalidArgumentException est bien levée
     *  - Vérifier que rien n'est persisté ni flushé en base
     */
    public function testCreateFromModelThrowsExceptionWhenModelIsIncomplete(): void
    {
        // On crée un mock de l'EntityManager.
        // createMock() vient de PHPUnit et permet de fabriquer un "faux" objet.
        $entityManager = $this->createMock(EntityManagerInterface::class);

        // On crée aussi un mock du repository.
        $measureNodeRepository = $this->createMock(MeasureNodeRepository::class);

        // On affirme qu'en cas de modèle incomplet, ces méthodes ne doivent jamais être appelées.
        // ->expects($this->never()) signifie : zéro appel attendu.
        $entityManager
            ->expects($this->never())
            ->method('persist');

        $entityManager
            ->expects($this->never())
            ->method('flush');

        $measureNodeRepository
            ->expects($this->never())
            ->method('findLeafNodesByReferential');

        // On instancie le service à tester avec les mocks.
        $creator = new CampaignCreator($entityManager, $measureNodeRepository);

        // On crée un modèle volontairement incomplet :
        // seule la propriété "name" est renseignée.
        $model = new CampaignCreationModel();
        $model->name = 'Campagne NIS2';

        // On indique à PHPUnit qu'on s'attend à une exception particulière.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les données de création de campagne sont incomplètes.');

        // Appel de la méthode testée.
        // Si l'exception n'est pas levée, le test échouera.
        $creator->createFromModel($model);
    }

    /**
     * Cas 2 : modèle complet, création correcte d'une campagne et de reviews.
     *
     * Objectif :
     *  - Vérifier que Campaign est correctement hydratée à partir du modèle
     *  - Vérifier qu'une MeasureReview est créée pour chaque feuille renvoyée par le repository
     *  - Vérifier que les persist() et flush() sont bien appelés
     *  - Vérifier que le statut initial des reviews est NON_COMPLIANT
     */
    public function testCreateFromModelCreatesCampaignAndReviewsForEachLeafNode(): void
    {
        // Création des mocks pour isoler le service.
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $measureNodeRepository = $this->createMock(MeasureNodeRepository::class);

        // Instance réelle du service, mais avec dépendances mockées.
        $creator = new CampaignCreator($entityManager, $measureNodeRepository);
        $referential = new Referential();
        $society = new Society();

        // On construit un modèle complet, cette fois avec toutes les propriétés requises.
        $model = new CampaignCreationModel();
        $model->name = 'Campagne 2026';
        $model->startDate = new DateTimeImmutable('2026-01-01');
        $model->endDate = new DateTimeImmutable('2026-12-31');
        $model->society = $society;
        $model->referential = $referential;

        // On prépare deux MeasureNode simulés : ce sont les "feuilles"
        // que le repository est censé renvoyer.
        $measureNode1 = $this->createMock(MeasureNode::class);
        $measureNode2 = $this->createMock(MeasureNode::class);
        $leafNodes = [$measureNode1, $measureNode2];

        // On programme le mock MeasureNodeRepository :
        //  - la méthode findLeafNodesByReferential() doit être appelée une seule fois
        //  - avec $referential comme argument
        //  - et renvoyer notre tableau de nodes simulés
        $measureNodeRepository
            ->expects($this->once())
            ->method('findLeafNodesByReferential')
            ->with($referential)
            ->willReturn($leafNodes);

        // On va enregistrer tous les objets passés à persist()
        // pour pouvoir les inspecter après l'appel à createFromModel().
        $persistedEntities = [];

        // L'EntityManager doit recevoir 3 appels à persist():
        //  1 pour la Campaign
        //  2 pour les deux MeasureReview
        $entityManager
            ->expects($this->exactly(3))
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persistedEntities): void {
                // Chaque fois que persist() est appelé, on stocke l'entité dans un tableau.
                $persistedEntities[] = $entity;
            });

        // On s'attend à un seul appel à flush() à la fin du traitement.
        $entityManager
            ->expects($this->once())
            ->method('flush');

        // ==== Appel de la méthode testée ====
        $campaign = $creator->createFromModel($model);

        // ==== Vérifications sur la campagne retournée ====

        // On vérifie qu'on a bien reçu une instance de Campaign.
        $this->assertInstanceOf(Campaign::class, $campaign);

        // On vérifie que les propriétés de Campaign correspondent au modèle.
        $this->assertSame('Campagne 2026', $campaign->getName());
        $this->assertSame($model->startDate, $campaign->getStartDate());
        $this->assertSame($model->endDate, $campaign->getEndDate());
        $this->assertSame($society, $campaign->getSociety());
        $this->assertSame($referential, $campaign->getReferential());

        // ==== Vérifications sur les entités persistées ====

        // On attend 3 entités persistées (1 Campaign + 2 MeasureReview).
        $this->assertCount(3, $persistedEntities);

        // Par convention, le service persiste d'abord la campagne,
        // puis les reviews. On le vérifie ici.
        $this->assertInstanceOf(Campaign::class, $persistedEntities[0]);
        $this->assertInstanceOf(MeasureReview::class, $persistedEntities[1]);
        $this->assertInstanceOf(MeasureReview::class, $persistedEntities[2]);

        /** @var MeasureReview $firstReview */
        $firstReview = $persistedEntities[1];
        /** @var MeasureReview $secondReview */
        $secondReview = $persistedEntities[2];

        // Les reviews doivent bien être rattachées à la campagne créée.
        $this->assertSame($campaign, $firstReview->getCampaign());
        $this->assertSame($campaign, $secondReview->getCampaign());

        // Et à chaque MeasureNode renvoyé par le repository.
        $this->assertSame($measureNode1, $firstReview->getMeasure());
        $this->assertSame($measureNode2, $secondReview->getMeasure());

        // Le statut initial doit être NON_COMPLIANT comme dans ton service.
        $this->assertSame(MeasureReviewStatus::NON_COMPLIANT, $firstReview->getStatus());
        $this->assertSame(MeasureReviewStatus::NON_COMPLIANT, $secondReview->getStatus());

        // On vérifie également que la campagne contient bien 2 MeasureReview
        // dans sa collection interne (via addMeasureReview()).
        $this->assertCount(2, $campaign->getMeasureReviews());
    }
}
