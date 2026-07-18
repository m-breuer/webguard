<?php

declare(strict_types=1);

namespace App\Support;

final class RobotsTxt
{
    public static function content(): string
    {
        $sitemapUrl = mb_rtrim((string) config('app.url'), '/') . route('sitemap', [], false);

        return implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            '',
            'Sitemap: ' . $sitemapUrl,
            '',
        ]);
    }
}
