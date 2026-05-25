<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Controllers\PublicFeatureController;
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
            'public-features.index',
            'monitoring-locations',
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
        $urls = array_map(
            fn (string $routeName): string => route($routeName),
            self::routeNames()
        );

        foreach (PublicFeatureController::slugs() as $slug) {
            $urls[] = route('public-features.show', $slug);
        }

        return $urls;
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
