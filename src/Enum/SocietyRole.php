<?php
// src/Enum/SocietyRole.php
/**
 * ADMIN : gère la société (ajout d’utilisateurs, gestion des campagnes, etc.).
 * MEMBER : utilise l’outil pour cette société (créer, modifier, etc. suivant ce que tu décideras).
 * READER : lecture seule (pratique pour des auditeurs, DPO externe, etc.).
 *
 */
namespace App\Enum;

enum SocietyRole: string
{
    case ADMIN  = 'ADMIN';
    case MEMBER = 'MEMBER';
    case READER = 'READER';
}
