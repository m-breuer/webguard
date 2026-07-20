<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentType: string
{
    case AVAILABILITY = 'availability';
    case PERFORMANCE = 'performance';
    case SECURITY = 'security';
    case DEPENDENCY = 'dependency';
    case CONFIGURATION = 'configuration';
    case OTHER = 'other';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
