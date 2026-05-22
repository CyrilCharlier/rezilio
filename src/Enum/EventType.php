<?php

namespace App\Enum;

enum EventType: string
{
    case LOGIN_SUCCESS           = 'auth.login.success';
    case LOGIN_FAILURE           = 'auth.login.failure';
    case LOGIN_REMEMBERED        = 'auth.login.remembered';
    case LOGOUT                  = 'auth.logout';

    case ACCOUNT_CREATED         = 'user.account.created';
    case ACCOUNT_DISABLED        = 'user.account.disabled';
    case ACCOUNT_ENABLED         = 'user.account.enabled';
    case ACCOUNT_UPDATED         = 'user.account.updated';
    case ACCOUNT_DELETED         = 'user.account.deleted';

    case PASSWORD_RESET_REQUEST  = 'auth.password.reset.requested';
    case PASSWORD_RESET_COMPLETED= 'auth.password.reset.completed';
    case PASSWORD_CHANGED        = 'auth.password.changed';

    case TWOFA_ENABLED           = 'auth.2fa.enabled';
    case TWOFA_DISABLED          = 'auth.2fa.disabled';
    case TWOFA_CHALLENGE_SUCCESS = 'auth.2fa.challenge.success';
    case TWOFA_CHALLENGE_FAILURE = 'auth.2fa.challenge.failure';

    CASE REFERENTIAL_IMPORT_FAILURE = 'referential.import.failure';
    CASE REFERENTIAL_IMPORT_SUCCESS = 'referential.import.success';
    CASE REFERENTIAL_EXPORT_FAILURE = 'referential.export.failure';
    CASE REFERENTIAL_EXPORT_SUCCESS = 'referential.export.success';
}
