<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationEventType: string
{
    case INCIDENT = 'incident';
    case RECOVERY = 'recovery';
    case SSL_EXPIRING = 'ssl_expiring';
    case SSL_EXPIRED = 'ssl_expired';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
