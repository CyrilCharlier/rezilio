<?php

namespace App\Enum;

enum RemediationActionStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::OPEN => 'Ouverte',
            self::IN_PROGRESS => 'En cours',
            self::DONE => 'Terminée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'text-bg-secondary',
            self::OPEN => 'text-bg-primary',
            self::IN_PROGRESS => 'text-bg-warning',
            self::DONE => 'text-bg-success',
            self::CANCELLED => 'text-bg-dark',
        };
    }
}
