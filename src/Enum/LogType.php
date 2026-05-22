<?php

namespace App\Enum;

enum LogType: string
{
    case AUTH_EVENT           = 'auth_event';
    case REFERENTIAL_EVENT    = 'referential_event';
}
