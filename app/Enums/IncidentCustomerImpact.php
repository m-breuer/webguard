<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentCustomerImpact: string
{
    case NONE = 'none';
    case DEGRADED = 'degraded';
    case OUTAGE = 'outage';
    case UNKNOWN = 'unknown';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
