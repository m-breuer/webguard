<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Http\Controllers\PublicFeatureController;
use Illuminate\Support\Str;
use JsonException;

class StructuredData
{
    /**
     * @return array<string, mixed>
     */
    public static function marketingPage(string $title, string $description, string $url, string $logoUrl): array
    {
        $baseUrl = url('/');
        $routeName = request()->route()?->getName();
        $graph = [
            self::organization($baseUrl, $logoUrl),
            self::website($baseUrl),
            self::softwareApplication($baseUrl),
            self::webPage($title, $description, $url, $baseUrl),
        ];

        $breadcrumbs = self::breadcrumbs($routeName, $url);

        if ($breadcrumbs !== []) {
            $graph[] = self::breadcrumbList($breadcrumbs, $url);
        }

        if ($routeName === 'public-features.index') {
            $featureList = self::featureItemList();

            $graph[] = $featureList;
            $graph[3]['mainEntity'] = ['@id' => $featureList['@id']];
        }

        if ($routeName === 'public-features.show') {
            $feature = self::currentFeature();

            if ($feature !== null) {
                $graph[] = $feature;
                $graph[3]['about'] = ['@id' => $feature['@id']];
                $graph[3]['mainEntity'] = ['@id' => $feature['@id']];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws JsonException
     */
    public static function toJson(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    private static function organization(string $baseUrl, string $logoUrl): array
    {
        return [
            '@type' => 'Organization',
            '@id' => $baseUrl . '#organization',
            'name' => __('app.name'),
            'url' => $baseUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function website(string $baseUrl): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $baseUrl . '#website',
            'name' => __('app.name'),
            'url' => $baseUrl,
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'publisher' => ['@id' => $baseUrl . '#organization'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function softwareApplication(string $baseUrl): array
    {
        return [
            '@type' => 'SoftwareApplication',
            '@id' => $baseUrl . '#software',
            'name' => __('app.name'),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'Website monitoring and incident communication',
            'operatingSystem' => 'Web',
            'description' => __('app.description'),
            'url' => route('welcome'),
            'publisher' => ['@id' => $baseUrl . '#organization'],
            'audience' => [
                '@type' => 'Audience',
                'audienceType' => 'SaaS teams, operators, developers, and website owners',
            ],
            'featureList' => self::featureNames(),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function webPage(string $title, string $description, string $url, string $baseUrl): array
    {
        return [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => $baseUrl . '#website'],
            'publisher' => ['@id' => $baseUrl . '#organization'],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function featureItemList(): array
    {
        return [
            '@type' => 'ItemList',
            '@id' => route('public-features.index') . '#feature-list',
            'name' => __('public_features.index.hero.title'),
            'itemListElement' => collect(PublicFeatureController::features())
                ->keys()
                ->values()
                ->map(fn (string $slug, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('public-features.show', $slug),
                    'name' => self::featureTitle($slug),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function currentFeature(): ?array
    {
        $slug = request()->route('feature');

        if (! is_string($slug) || ! array_key_exists($slug, PublicFeatureController::features())) {
            return null;
        }

        $featureKey = PublicFeatureController::features()[$slug]['key'];

        return [
            '@type' => 'DefinedTerm',
            '@id' => route('public-features.show', $slug) . '#feature',
            'name' => self::featureTitle($slug),
            'description' => (string) __('public_features.features.' . $featureKey . '.lead'),
            'url' => route('public-features.show', $slug),
            'inDefinedTermSet' => [
                '@type' => 'DefinedTermSet',
                '@id' => route('public-features.index') . '#feature-list',
                'name' => __('public_features.index.hero.title'),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private static function featureNames(): array
    {
        return collect(PublicFeatureController::features())
            ->keys()
            ->map(fn (string $slug): string => self::featureTitle($slug))
            ->values()
            ->all();
    }

    private static function featureTitle(string $slug): string
    {
        $feature = PublicFeatureController::features()[$slug];

        return (string) __('public_features.features.' . $feature['key'] . '.title');
    }

    /**
     * @return list<array{name: string, item: string}>
     */
    private static function breadcrumbs(?string $routeName, string $url): array
    {
        $breadcrumbs = [
            ['name' => __('app.name'), 'item' => route('welcome')],
        ];

        if ($routeName === 'welcome') {
            return $breadcrumbs;
        }

        if ($routeName === 'public-features.index') {
            $breadcrumbs[] = ['name' => __('public_features.footer_link'), 'item' => $url];

            return $breadcrumbs;
        }

        if ($routeName === 'public-features.show') {
            $slug = request()->route('feature');

            $breadcrumbs[] = ['name' => __('public_features.footer_link'), 'item' => route('public-features.index')];

            if (is_string($slug) && array_key_exists($slug, PublicFeatureController::features())) {
                $breadcrumbs[] = ['name' => self::featureTitle($slug), 'item' => $url];
            }

            return $breadcrumbs;
        }

        $routeLabel = Str::of((string) $routeName)->replace('-', ' ')->title()->toString();

        if ($routeLabel !== '') {
            $breadcrumbs[] = ['name' => $routeLabel, 'item' => $url];
        }

        return $breadcrumbs;
    }

    /**
     * @param  list<array{name: string, item: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    private static function breadcrumbList(array $breadcrumbs, string $url): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $url . '#breadcrumb',
            'itemListElement' => collect($breadcrumbs)
                ->values()
                ->map(fn (array $breadcrumb, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $breadcrumb['name'],
                    'item' => $breadcrumb['item'],
                ])
                ->all(),
        ];
    }
}
