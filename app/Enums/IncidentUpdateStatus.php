<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentUpdateStatus: string
{
    case INVESTIGATING = 'investigating';
    case IDENTIFIED = 'identified';
    case MONITORING = 'monitoring';
    case RESOLVED = 'resolved';

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
            self::RESOLVED => 'success',
            self::MONITORING => 'info',
            self::IDENTIFIED => 'warning',
            self::INVESTIGATING => 'danger',
        };
    }
}
