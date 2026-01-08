<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum MonitoringLifecycleStatus
 *
 * Represents the lifecycle state of a monitoring check:
 * - ACTIVE: Monitoring runs as usual.
 * - PAUSED: Monitoring is temporarily disabled.
 */
enum MonitoringLifecycleStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';

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
