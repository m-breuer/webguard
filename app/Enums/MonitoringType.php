<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum MonitoringType
 *
 * Defines the types of monitoring supported by the system:
 * - HTTP: Checks if an HTTP service is reachable and returns a valid response.
 * - PING: Verifies if a host is reachable via ICMP (ping).
 * - KEYWORD: Looks for a specific keyword in the response of a web request.
 * - PORT: Checks if a specific port on a host is open and accepting connections.
 */
enum MonitoringType: string
{
    case HTTP = 'http';
    case PING = 'ping';
    case KEYWORD = 'keyword';
    case PORT = 'port';

    /**
     * Get all enum values as a simple array of strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
