<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportedLanguage: string
{
    case EN = 'en';
    case DE = 'de';

    public static function default(): self
    {
        return self::EN;
    }

    public static function toArray(): array
    {
        return array_reduce(self::cases(), function ($carry, $item) {
            $carry[$item->value] = $item->label();

            return $carry;
        }, []);
    }

    public function label(): string
    {
        return match ($this) {
            self::EN => 'English',
            self::DE => 'Deutsch',
        };
    }
}
