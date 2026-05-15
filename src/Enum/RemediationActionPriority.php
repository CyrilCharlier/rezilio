<?php

namespace App\Enum;

enum RemediationActionPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Basse',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Haute',
            self::CRITICAL => 'Critique',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'text-bg-secondary',
            self::MEDIUM => 'text-bg-info',
            self::HIGH => 'text-bg-warning',
            self::CRITICAL => 'text-bg-danger',
        };
    }
}
