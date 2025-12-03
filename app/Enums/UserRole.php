<?php

namespace App\Enums;

/**
 * Enum UserRole
 *
 * Defines available user roles within the application:
 * - ADMIN: Full administrative access to all features and settings.
 * - REGULAR: Standard user access to monitoring features.
 * - GUEST: Limited access, typically read-only or restricted functionality.
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case REGULAR = 'regular';
    case GUEST = 'guest';

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
