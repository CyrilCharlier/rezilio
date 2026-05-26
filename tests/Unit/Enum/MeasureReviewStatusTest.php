<?php

namespace App\Tests\Unit\Enum;

use App\Enum\MeasureReviewStatus;
use PHPUnit\Framework\TestCase;

class MeasureReviewStatusTest extends TestCase
{
    /**
     * Vérifie que les cas attendus existent bien dans l'enum.
     *
     * Ce test a deux intérêts :
     * - documenter le vocabulaire métier disponible ;
     * - détecter une régression si un cas est renommé ou supprimé.
     */
    public function testCasesExist(): void
    {
        $cases = MeasureReviewStatus::cases();

        $this->assertCount(4, $cases);

        $this->assertSame(MeasureReviewStatus::BLOCKED, $cases[0]);
        $this->assertSame(MeasureReviewStatus::NON_COMPLIANT, $cases[1]);
        $this->assertSame(MeasureReviewStatus::IN_PROGRESS, $cases[2]);
        $this->assertSame(MeasureReviewStatus::COMPLIANT, $cases[3]);
    }

    /**
     * Vérifie les valeurs portées par chaque cas.
     *
     * Comme ton enum est de type string, chaque case possède une valeur.
     * Ici on verrouille le contrat exact utilisé dans l'application.
     */
    public function testCaseValues(): void
    {
        $this->assertSame('BLOCKED', MeasureReviewStatus::BLOCKED->value);
        $this->assertSame('NON_COMPLIANT', MeasureReviewStatus::NON_COMPLIANT->value);
        $this->assertSame('IN_PROGRESS', MeasureReviewStatus::IN_PROGRESS->value);
        $this->assertSame('COMPLIANT', MeasureReviewStatus::COMPLIANT->value);
    }

    /**
     * Vérifie qu'on peut reconstruire les cas depuis leurs valeurs.
     *
     * Ce test est utile car Doctrine et les formulaires manipulent souvent
     * les enums à partir de leur valeur string.
     */
    public function testFromValue(): void
    {
        $this->assertSame(
            MeasureReviewStatus::BLOCKED,
            MeasureReviewStatus::from('BLOCKED')
        );

        $this->assertSame(
            MeasureReviewStatus::NON_COMPLIANT,
            MeasureReviewStatus::from('NON_COMPLIANT')
        );

        $this->assertSame(
            MeasureReviewStatus::IN_PROGRESS,
            MeasureReviewStatus::from('IN_PROGRESS')
        );

        $this->assertSame(
            MeasureReviewStatus::COMPLIANT,
            MeasureReviewStatus::from('COMPLIANT')
        );
    }
}
