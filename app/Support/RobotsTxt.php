<?php

declare(strict_types=1);

namespace App\Support;

final class RobotsTxt
{
    public static function content(): string
    {
        return implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            '',
        ]);
    }
}
