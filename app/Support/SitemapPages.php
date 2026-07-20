<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class SitemapPages
{
    /**
     * @return list<string>
     */
    public static function routeNames(): array
    {
        return [
            'imprint',
            'terms-of-use',
            'gdpr',
        ];
    }

    /**
     * @return list<string>
     */
    public static function urls(): array
    {
        return array_map(
            fn (string $routeName): string => route($routeName),
            self::routeNames()
        );
    }

    public static function sitemap(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (self::urls() as $url) {
            $sitemap->add(Url::create($url));
        }

        return $sitemap;
    }
}
