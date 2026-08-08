<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public static function findToken($token): ?static
    {
        $accessToken = parent::findToken($token);

        if (! $accessToken instanceof static || $accessToken->revoked_at !== null) {
            return null;
        }

        return $accessToken;
    }
}
