<?php

namespace App\Enum;

enum MeasureReviewStatus: string
{
    case BLOCKED = 'BLOCKED';
    case NON_COMPLIANT = 'NON_COMPLIANT';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLIANT = 'COMPLIANT';
}
