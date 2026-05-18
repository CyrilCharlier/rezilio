<?php

// src/Security/TotpSecretGenerator.php
namespace App\Security;

class TotpSecretGenerator
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $chars = self::BASE32_ALPHABET;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }
}
