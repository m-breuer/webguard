<?php

declare(strict_types=1);

namespace App\Support\Seo;

use JsonException;

class StructuredData
{
    /**
     * @return array<string, mixed>
     */
    public static function legalPage(string $title, string $description, string $url, string $logoUrl): array
    {
        $baseUrl = url('/');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $baseUrl . '#organization',
                    'name' => __('app.name'),
                    'url' => $baseUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $logoUrl,
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $baseUrl . '#website',
                    'name' => __('app.name'),
                    'url' => $baseUrl,
                    'inLanguage' => str_replace('_', '-', app()->getLocale()),
                    'publisher' => ['@id' => $baseUrl . '#organization'],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $url . '#webpage',
                    'url' => $url,
                    'name' => $title,
                    'description' => $description,
                    'isPartOf' => ['@id' => $baseUrl . '#website'],
                    'publisher' => ['@id' => $baseUrl . '#organization'],
                    'inLanguage' => str_replace('_', '-', app()->getLocale()),
                ],
            ],
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
}
