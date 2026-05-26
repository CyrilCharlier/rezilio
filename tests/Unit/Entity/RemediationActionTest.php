<?php

namespace App\Tests\Unit\Entity;

use App\Entity\MeasureReview;
use App\Entity\RemediationAction;
use App\Enum\RemediationActionPriority;
use App\Enum\RemediationActionStatus;
use PHPUnit\Framework\TestCase;

class RemediationActionTest extends TestCase
{
    /**
     * Vérifie l'état initial d'une nouvelle action.
     */
    public function testInitialState(): void
    {
        $action = new RemediationAction();

        $this->assertNull($action->getTitle());
        $this->assertNull($action->getDescription());
        $this->assertSame(RemediationActionPriority::MEDIUM, $action->getPriority());
        $this->assertSame(RemediationActionStatus::DRAFT, $action->getStatus());
        $this->assertNull($action->getDueDate());
        $this->assertNull($action->getMeasureReview());
        $this->assertNull($action->getCreatedAt());
        $this->assertNull($action->getUpdatedAt());
    }

    /**
     * Vérifie les setters et getters principaux.
     */
    public function testSettersAndGetters(): void
    {
        $action = new RemediationAction();
        $review = new MeasureReview();
        $dueDate = new \DateTime('2026-12-31');

        $action->setTitle('Mettre en place la MFA');
        $action->setDescription('Activer l’authentification multifacteur sur les comptes sensibles');
        $action->setPriority(RemediationActionPriority::HIGH);
        $action->setStatus(RemediationActionStatus::OPEN);
        $action->setDueDate($dueDate);
        $action->setMeasureReview($review);

        $this->assertSame('Mettre en place la MFA', $action->getTitle());
        $this->assertSame('Activer l’authentification multifacteur sur les comptes sensibles', $action->getDescription());
        $this->assertSame(RemediationActionPriority::HIGH, $action->getPriority());
        $this->assertSame(RemediationActionStatus::OPEN, $action->getStatus());
        $this->assertSame($dueDate, $action->getDueDate());
        $this->assertSame($review, $action->getMeasureReview());
    }

    /**
     * Vérifie que onPrePersist initialise les timestamps.
     */
    public function testOnPrePersistInitializesTimestamps(): void
    {
        $action = new RemediationAction();

        $this->assertNull($action->getCreatedAt());
        $this->assertNull($action->getUpdatedAt());

        $action->onPrePersist();

        $this->assertNotNull($action->getCreatedAt());
        $this->assertNotNull($action->getUpdatedAt());
        $this->assertEquals($action->getCreatedAt(), $action->getUpdatedAt());
    }

    /**
     * Vérifie que onPreUpdate modifie updatedAt.
     */
    public function testOnPreUpdateRefreshesUpdatedAt(): void
    {
        $action = new RemediationAction();

        $action->onPrePersist();
        $firstUpdatedAt = $action->getUpdatedAt();

        usleep(1000);

        $action->onPreUpdate();

        $this->assertNotNull($action->getUpdatedAt());
        $this->assertNotEquals($firstUpdatedAt, $action->getUpdatedAt());
    }
}
