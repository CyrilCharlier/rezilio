<?php

namespace App\Tests\Unit\Enum;

use App\Enum\RemediationActionStatus;
use PHPUnit\Framework\TestCase;

class RemediationActionStatusTest extends TestCase
{
    public function testLabelReturnsExpectedFrenchValue(): void
    {
        $this->assertSame('Brouillon', RemediationActionStatus::DRAFT->label());
        $this->assertSame('Ouverte', RemediationActionStatus::OPEN->label());
        $this->assertSame('En cours', RemediationActionStatus::IN_PROGRESS->label());
        $this->assertSame('Terminée', RemediationActionStatus::DONE->label());
        $this->assertSame('Annulée', RemediationActionStatus::CANCELLED->label());
    }

    public function testBadgeClassReturnsExpectedBootstrapClass(): void
    {
        $this->assertSame('text-bg-secondary', RemediationActionStatus::DRAFT->badgeClass());
        $this->assertSame('text-bg-primary', RemediationActionStatus::OPEN->badgeClass());
        $this->assertSame('text-bg-warning', RemediationActionStatus::IN_PROGRESS->badgeClass());
        $this->assertSame('text-bg-success', RemediationActionStatus::DONE->badgeClass());
        $this->assertSame('text-bg-dark', RemediationActionStatus::CANCELLED->badgeClass());
    }
}
