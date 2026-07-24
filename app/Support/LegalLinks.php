<?php

declare(strict_types=1);

namespace App\Support;

final class LegalLinks
{
    public static function imprint(): string
    {
        return self::url('imprint');
    }

    public static function termsOfUse(): string
    {
        return self::url('terms-of-use');
    }

    public static function privacyPolicy(): string
    {
        return self::url('gdpr');
    }

    private static function url(string $path): string
    {
        $baseUrl = config('app.marketing_url') ?: 'http://localhost:4321';

        return mb_rtrim((string) $baseUrl, '/') . '/' . $path;
    }
}
