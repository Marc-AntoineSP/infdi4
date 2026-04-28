<?php

declare(strict_types=1);

namespace App\Utility;

enum ApplicationEnum: string {
    public const string HEADER_LOCATION = 'Location: ';
    //----- MODELS -------//
    public const string TOKEN_PARAM = ':token';
    public const string USER_ID_PARAM = ':user_id';
    public const string EXPIRES_AT_PARAM = ':expires_at';
}