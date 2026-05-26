<?php

namespace App\Tests\Unit\Security;

use App\Security\TotpSecretGenerator;
use PHPUnit\Framework\TestCase;

class TotpSecretGeneratorTest extends TestCase
{
    public function testGenerateSecretReturns32CharactersByDefault(): void
    {
        $generator = new TotpSecretGenerator();

        $secret = $generator->generateSecret();

        $this->assertSame(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
    }

    public function testGenerateSecretRespectsCustomLength(): void
    {
        $generator = new TotpSecretGenerator();

        $secret = $generator->generateSecret(16);

        $this->assertSame(16, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16}$/', $secret);
    }

    public function testGenerateSecretOnlyUsesBase32Alphabet(): void
    {
        $generator = new TotpSecretGenerator();

        $secret = $generator->generateSecret(64);

        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertDoesNotMatchRegularExpression('/[0189]/', $secret);
    }

    public function testGenerateSecretReturnsEmptyStringWhenLengthIsZero(): void
    {
        $generator = new TotpSecretGenerator();

        $secret = $generator->generateSecret(0);

        $this->assertSame('', $secret);
    }
}
