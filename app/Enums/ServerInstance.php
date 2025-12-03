<?php

namespace App\Enums;

enum ServerInstance: string
{
    case DE_1 = 'de-1';

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
