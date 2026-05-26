<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Evidence;
use App\Entity\MeasureReview;
use App\Enum\MeasureReviewStatus;
use PHPUnit\Framework\TestCase;

class MeasureReviewTest extends TestCase
{
    /**
     * Vérifie l'état initial de l'entité.
     *
     * Ce test sert à documenter ce qu'on obtient immédiatement
     * après un "new MeasureReview()".
     */
    public function testInitialState(): void
    {
        $review = new MeasureReview();

        // D'après ton entité, le statut par défaut doit être NON_COMPLIANT.
        $this->assertSame(MeasureReviewStatus::NON_COMPLIANT, $review->getStatus());

        // Les collections sont initialisées dans le constructeur
        // et doivent donc être vides au départ.
        $this->assertCount(0, $review->getEvidences());
        $this->assertCount(0, $review->getRemediationActions());

        // Le compteur d'evidences doit refléter la collection.
        $this->assertSame(0, $review->getEvidenceCount());

        // Les timestamps ne sont pas encore définis tant qu'on n'a pas simulé
        // un cycle Doctrine (PrePersist / PreUpdate).
        $this->assertNull($review->getCreatedAt());
        $this->assertNull($review->getUpdatedAt());
    }

    /**
     * Vérifie qu'ajouter une evidence :
     * - l'ajoute à la collection ;
     * - met à jour le côté inverse sur Evidence ;
     * - n'ajoute pas de doublon si on réajoute la même instance.
     */
    public function testAddEvidenceAddsEvidenceAndUpdatesInverseSide(): void
    {
        $review = new MeasureReview();
        $evidence = new Evidence();

        $review->addEvidence($evidence);

        // L'evidence doit maintenant être présente dans la collection.
        $this->assertCount(1, $review->getEvidences());
        $this->assertTrue($review->getEvidences()->contains($evidence));
        $this->assertSame(1, $review->getEvidenceCount());

        // Très important sur une relation bidirectionnelle :
        // Evidence doit maintenant pointer vers la review.
        $this->assertSame($review, $evidence->getMeasureReview());

        // Si on réajoute exactement le même objet,
        // on ne doit pas créer de doublon.
        $review->addEvidence($evidence);

        $this->assertCount(1, $review->getEvidences());
        $this->assertSame(1, $review->getEvidenceCount());
    }

    /**
     * Vérifie qu'en supprimant une evidence :
     * - elle disparaît bien de la collection ;
     * - la relation inverse est remise à null.
     */
    public function testRemoveEvidenceRemovesEvidenceAndClearsInverseSide(): void
    {
        $review = new MeasureReview();
        $evidence = new Evidence();

        // On prépare l'état initial.
        $review->addEvidence($evidence);

        $this->assertCount(1, $review->getEvidences());
        $this->assertSame($review, $evidence->getMeasureReview());

        // On retire l'evidence.
        $review->removeEvidence($evidence);

        // Elle ne doit plus être dans la collection.
        $this->assertCount(0, $review->getEvidences());
        $this->assertFalse($review->getEvidences()->contains($evidence));
        $this->assertSame(0, $review->getEvidenceCount());

        // Et la relation inverse doit être nettoyée.
        $this->assertNull($evidence->getMeasureReview());
    }

    /**
     * Vérifie le comportement de onPrePersist().
     *
     * Dans ton entité :
     * - createdAt est défini s'il est null ;
     * - updatedAt est défini s'il est null.
     */
    public function testOnPrePersistInitializesTimestamps(): void
    {
        $review = new MeasureReview();

        $this->assertNull($review->getCreatedAt());
        $this->assertNull($review->getUpdatedAt());

        $review->onPrePersist();

        $this->assertNotNull($review->getCreatedAt());
        $this->assertNotNull($review->getUpdatedAt());

        // Sur un premier persist, les deux timestamps sont initialisés
        // dans le même passage.
        $this->assertEquals($review->getCreatedAt(), $review->getUpdatedAt());
    }

    /**
     * Vérifie le comportement de onPreUpdate().
     *
     * Cette méthode doit rafraîchir updatedAt.
     */
    public function testOnPreUpdateRefreshesUpdatedAt(): void
    {
        $review = new MeasureReview();

        $review->onPrePersist();
        $firstUpdatedAt = $review->getUpdatedAt();

        // Petite pause pour éviter d'obtenir deux timestamps identiques
        // sur une machine très rapide.
        usleep(1000);

        $review->onPreUpdate();

        $this->assertNotNull($review->getUpdatedAt());
        $this->assertNotEquals($firstUpdatedAt, $review->getUpdatedAt());
    }

    public function testAddRemediationActionAddsActionAndUpdatesInverseSide(): void
    {
        $review = new MeasureReview();
        $action = new \App\Entity\RemediationAction();

        $review->addRemediationAction($action);

        // L'action doit être ajoutée à la collection.
        $this->assertCount(1, $review->getRemediationActions());
        $this->assertTrue($review->getRemediationActions()->contains($action));

        // Et le côté inverse doit être mis à jour.
        $this->assertSame($review, $action->getMeasureReview());

        // Si on réajoute la même instance, il ne doit pas y avoir de doublon.
        $review->addRemediationAction($action);

        $this->assertCount(1, $review->getRemediationActions());
    }

    public function testRemoveRemediationActionRemovesActionAndClearsInverseSide(): void
    {
        $review = new MeasureReview();
        $action = new \App\Entity\RemediationAction();

        $review->addRemediationAction($action);

        $this->assertCount(1, $review->getRemediationActions());
        $this->assertSame($review, $action->getMeasureReview());

        $review->removeRemediationAction($action);

        // L'action ne doit plus être présente.
        $this->assertCount(0, $review->getRemediationActions());
        $this->assertFalse($review->getRemediationActions()->contains($action));

        // Le côté inverse doit être nettoyé.
        $this->assertNull($action->getMeasureReview());
    }
}
