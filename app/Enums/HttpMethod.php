<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum HttpMethod
 *
 * Represents the HTTP methods used for monitoring.
 */
enum HttpMethod: string
{
    case GET = 'get';
    case POST = 'post';
    case PUT = 'put';
    case PATCH = 'patch';
    case DELETE = 'delete';

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
