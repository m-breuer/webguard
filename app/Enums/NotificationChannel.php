<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case SLACK = 'slack';
    case TELEGRAM = 'telegram';
    case DISCORD = 'discord';
    case WEBHOOK = 'webhook';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

