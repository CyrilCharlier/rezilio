<?php

namespace App\Tests\Unit\Enum;

use App\Enum\RemediationActionPriority;
use PHPUnit\Framework\TestCase;

class RemediationActionPriorityTest extends TestCase
{
    public function testLabelReturnsExpectedFrenchValue(): void
    {
        $this->assertSame('Basse', RemediationActionPriority::LOW->label());
        $this->assertSame('Moyenne', RemediationActionPriority::MEDIUM->label());
        $this->assertSame('Haute', RemediationActionPriority::HIGH->label());
        $this->assertSame('Critique', RemediationActionPriority::CRITICAL->label());
    }

    public function testBadgeClassReturnsExpectedBootstrapClass(): void
    {
        $this->assertSame('text-bg-secondary', RemediationActionPriority::LOW->badgeClass());
        $this->assertSame('text-bg-info', RemediationActionPriority::MEDIUM->badgeClass());
        $this->assertSame('text-bg-warning', RemediationActionPriority::HIGH->badgeClass());
        $this->assertSame('text-bg-danger', RemediationActionPriority::CRITICAL->badgeClass());
    }
}
