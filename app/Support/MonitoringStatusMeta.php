<?php

declare(strict_types=1);

namespace App\Support;

final class MonitoringStatusMeta
{
    public static function identifier(?int $statusCode, bool $maintenanceActive = false): string
    {
        if ($maintenanceActive) {
            return 'maintenance';
        }

        if ($statusCode === null) {
            return 'unknown';
        }

        return match (true) {
            $statusCode >= 200 && $statusCode <= 299 => 'success',
            $statusCode >= 300 && $statusCode <= 399 => 'redirect',
            $statusCode >= 400 && $statusCode <= 499 => 'client_error',
            $statusCode >= 500 && $statusCode <= 599 => 'server_error',
            default => 'unknown',
        };
    }

    public static function statusIdentifier(?int $statusCode, bool $maintenanceActive = false): string
    {
        return 'status.' . self::identifier($statusCode, $maintenanceActive);
    }

    public static function statusKey(?int $statusCode, bool $maintenanceActive = false): string
    {
        return 'notifications.status.' . self::identifier($statusCode, $maintenanceActive);
    }

    public static function badgeType(string $statusIdentifier): string
    {
        return match ($statusIdentifier) {
            'success' => 'success',
            'redirect' => 'info',
            'client_error' => 'warning',
            'server_error' => 'danger',
            default => 'neutral',
        };
    }
}
