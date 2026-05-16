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
            'welcome',
            'monitoring-locations',
            'imprint',
            'terms-of-use',
            'gdpr',
        ];
    }

    public static function sitemap(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (self::routeNames() as $routeName) {
            $sitemap->add(Url::create(route($routeName)));
        }

        return $sitemap;
    }
}
