<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function badgeType(): string
    {
        return match ($this) {
            self::CRITICAL => 'danger',
            self::HIGH => 'warning',
            self::MEDIUM => 'info',
            self::LOW => 'success',
        };
    }
}
