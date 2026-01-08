<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum MonitoringStatus
 *
 * Represents the possible states of a monitoring check.
 * - UP: The monitoring check was successful.
 * - DOWN: The monitoring check failed.
 * - UNKNOWN: The status of the monitoring check is unknown.
 */
enum MonitoringStatus: string
{
    case UP = 'up';
    case DOWN = 'down';
    case UNKNOWN = 'unknown';

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
