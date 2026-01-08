<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case SSL_EXPIRY = 'ssl_expiry';
    case STATUS_CHANGE = 'status_change';

    /**
     * Get an array of all enum values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
