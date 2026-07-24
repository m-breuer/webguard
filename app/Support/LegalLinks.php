<?php

declare(strict_types=1);

namespace App\Support;

final class LegalLinks
{
    public static function imprint(): string
    {
        return self::url('imprint', route('imprint'));
    }

    public static function termsOfUse(): string
    {
        return self::url('terms-of-use', route('terms-of-use'));
    }

    public static function privacyPolicy(): string
    {
        return self::url('gdpr', route('gdpr'));
    }

    public static function isExternal(): bool
    {
        return filled(config('app.marketing_legal_url'));
    }

    private static function url(string $path, string $fallback): string
    {
        $baseUrl = config('app.marketing_legal_url');

        if (! filled($baseUrl)) {
            return $fallback;
        }

        return mb_rtrim((string) $baseUrl, '/') . '/' . $path;
    }
}
