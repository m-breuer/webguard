<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentContributingCategory: string
{
    case CODE = 'code';
    case INFRASTRUCTURE = 'infrastructure';
    case DEPENDENCY = 'dependency';
    case CONFIGURATION = 'configuration';
    case PROCESS = 'process';
    case UNKNOWN = 'unknown';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
