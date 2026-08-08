<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiKeyAbility: string
{
    case SERVER_HEALTH_WRITE = 'server-health:write';
    case ANALYTICS_READ = 'analytics:read';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
