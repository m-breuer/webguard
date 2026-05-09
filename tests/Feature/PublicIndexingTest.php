<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleXMLElement;
use Tests\TestCase;

class PublicIndexingTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function indexablePublicRouteProvider(): array
    {
        return collect(self::expectedIndexablePageRouteNames())
            ->mapWithKeys(fn (string $routeName): array => [$routeName => [$routeName]])
            ->put('sitemap', ['sitemap'])
            ->all();
    }

    #[DataProvider('indexablePublicRouteProvider')]
    public function test_indexable_public_routes_are_publicly_cacheable_for_guests(string $routeName): void
    {
        $testResponse = $this->get(route($routeName));

        $testResponse->assertOk();

        $cacheControl = (string) $testResponse->headers->get('Cache-Control');

        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
        $this->assertStringContainsString('s-maxage=3600', $cacheControl);
        $this->assertStringNotContainsString('private', $cacheControl);
        $this->assertStringNotContainsString('no-cache', $cacheControl);
    }

    public function test_indexable_public_routes_do_not_start_sessions_or_queue_cookies(): void
    {
        foreach (self::indexablePublicRouteProvider() as [$routeName]) {
            $testResponse = $this->withCookie(config('session.cookie'), 'existing-session-id')
                ->get(route($routeName));

            $testResponse->assertOk();
            $testResponse->assertHeaderMissing('Set-Cookie');
            $this->assertEmpty($testResponse->headers->getCookies());
        }
    }

    public function test_indexable_public_routes_are_publicly_cacheable_for_head_requests(): void
    {
        foreach (self::indexablePublicRouteProvider() as [$routeName]) {
            $testResponse = $this->withCookie(config('session.cookie'), 'existing-session-id')
                ->head(route($routeName));

            $testResponse->assertOk();
            $testResponse->assertHeaderMissing('Set-Cookie');

            $cacheControl = (string) $testResponse->headers->get('Cache-Control');

            $this->assertStringContainsString('public', $cacheControl);
            $this->assertStringContainsString('max-age=300', $cacheControl);
            $this->assertStringContainsString('s-maxage=3600', $cacheControl);
            $this->assertStringNotContainsString('private', $cacheControl);
            $this->assertStringNotContainsString('no-cache', $cacheControl);
        }
    }

    public function test_authenticated_public_page_responses_are_served_as_sessionless_public_pages(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->get(route('welcome'));

        $testResponse->assertOk();

        $cacheControl = (string) $testResponse->headers->get('Cache-Control');

        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringNotContainsString('private', $cacheControl);
        $testResponse->assertSeeText(__('welcome.nav.login'));
        $testResponse->assertDontSeeHtml(route('dashboard'));
        $testResponse->assertDontSeeHtml('name="csrf-token"');
    }

    public function test_sitemap_does_not_list_robots_blocked_legal_pages(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        foreach (self::expectedIndexablePageRouteNames() as $routeName) {
            $testResponse->assertSeeHtml(route($routeName));
        }

        $testResponse->assertDontSeeHtml(route('imprint'));
        $testResponse->assertDontSeeHtml(route('terms-of-use'));
        $testResponse->assertDontSeeHtml(route('gdpr'));
    }

    public function test_dynamic_sitemap_lists_exactly_the_indexable_public_pages(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();

        $this->assertSame(
            $this->expectedIndexablePageUrls(),
            $this->sortedSitemapLocations($testResponse->getContent())
        );
    }

    public function test_generated_sitemap_does_not_list_robots_blocked_legal_pages(): void
    {
        $sitemapPath = public_path('sitemap.xml');

        if (file_exists($sitemapPath)) {
            unlink($sitemapPath);
        }

        $this->artisan('sitemap:generate')->assertSuccessful();

        $sitemapContent = file_get_contents($sitemapPath);

        $this->assertIsString($sitemapContent);
        foreach (self::expectedIndexablePageRouteNames() as $routeName) {
            $this->assertStringContainsString(route($routeName), $sitemapContent);
        }

        $this->assertStringNotContainsString(route('imprint'), $sitemapContent);
        $this->assertStringNotContainsString(route('terms-of-use'), $sitemapContent);
        $this->assertStringNotContainsString(route('gdpr'), $sitemapContent);

        unlink($sitemapPath);
    }

    public function test_generated_sitemap_lists_exactly_the_indexable_public_pages(): void
    {
        $sitemapPath = public_path('sitemap.xml');

        if (file_exists($sitemapPath)) {
            unlink($sitemapPath);
        }

        $this->artisan('sitemap:generate')->assertSuccessful();

        $sitemapContent = file_get_contents($sitemapPath);

        $this->assertIsString($sitemapContent);
        $this->assertSame(
            $this->expectedIndexablePageUrls(),
            $this->sortedSitemapLocations($sitemapContent)
        );

        unlink($sitemapPath);
    }

    /**
     * @return list<string>
     */
    private static function expectedIndexablePageRouteNames(): array
    {
        return [
            'welcome',
            'monitoring-locations',
        ];
    }

    /**
     * @return list<string>
     */
    private function expectedIndexablePageUrls(): array
    {
        $urls = array_map(
            fn (string $routeName): string => route($routeName),
            self::expectedIndexablePageRouteNames()
        );

        sort($urls);

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function sitemapLocations(string $sitemapContent): array
    {
        $sitemap = simplexml_load_string($sitemapContent);

        $this->assertNotFalse($sitemap);

        $sitemap->registerXPathNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $locations = $sitemap->xpath('//sitemap:loc');

        $this->assertIsArray($locations);

        return array_values(array_map(
            fn (SimpleXMLElement $location): string => (string) $location,
            $locations
        ));
    }

    /**
     * @return list<string>
     */
    private function sortedSitemapLocations(string $sitemapContent): array
    {
        $locations = $this->sitemapLocations($sitemapContent);

        sort($locations);

        return $locations;
    }
}
