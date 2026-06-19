<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusPageComponentSource: string
{
    case MANUAL = 'manual';
    case MONITORING_GROUP = 'monitoring_group';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
