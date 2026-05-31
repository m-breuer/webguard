<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\PublicFeatureController;
use App\Models\Package;
use App\Models\User;
use App\Support\SitemapPages;
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleXMLElement;
use Tests\TestCase;

class PublicIndexingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureImprint();
    }

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

    /**
     * @return array<string, array{0: string}>
     */
    public static function indexablePublicFeatureProvider(): array
    {
        return collect(PublicFeatureController::slugs())
            ->mapWithKeys(fn (string $slug): array => [$slug => [$slug]])
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

    #[DataProvider('indexablePublicFeatureProvider')]
    public function test_indexable_public_feature_pages_are_publicly_cacheable_for_guests(string $slug): void
    {
        $testResponse = $this->get(route('public-features.show', $slug));

        $testResponse->assertOk();

        $cacheControl = (string) $testResponse->headers->get('Cache-Control');

        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
        $this->assertStringContainsString('s-maxage=3600', $cacheControl);
        $this->assertStringNotContainsString('private', $cacheControl);
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

    public function test_indexable_marketing_pages_emit_search_metadata(): void
    {
        foreach (self::expectedIndexablePageRouteNames() as $routeName) {
            $testResponse = $this->get(route($routeName));

            $testResponse->assertOk();
            $testResponse->assertSeeHtml('<meta name="robots" content="index, follow">');
            $testResponse->assertSeeHtml('<meta name="description"');
            $testResponse->assertSeeHtml('<meta property="og:title"');
            $testResponse->assertSeeHtml('<meta property="og:description"');
            $testResponse->assertSeeHtml('<meta name="twitter:card" content="summary_large_image">');
            $testResponse->assertSeeHtml(sprintf('<link rel="canonical" href="%s">', route($routeName)));
        }
    }

    public function test_welcome_landing_page_exposes_structured_seo_data(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('<script type="application/ld+json">');
        $testResponse->assertSeeHtml('"@type":"SoftwareApplication"');
        $testResponse->assertSeeHtml('"applicationCategory":"BusinessApplication"');
        $testResponse->assertSeeHtml('"url":"' . route('welcome') . '"');
        $testResponse->assertSeeHtml('"price":"0"');
        $testResponse->assertSeeHtml('"priceCurrency":"EUR"');
    }

    public function test_sitemap_lists_all_crawlable_public_pages(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        foreach (self::expectedIndexablePageRouteNames() as $routeName) {
            $testResponse->assertSeeHtml(route($routeName));
        }
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

    public function test_generated_sitemap_lists_all_crawlable_public_pages(): void
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

        unlink($sitemapPath);
    }

    public function test_robots_txt_allows_all_pages_to_be_crawled(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robotsContent);
        $this->assertStringContainsString('User-agent: *', $robotsContent);
        $this->assertStringContainsString('Allow: /', $robotsContent);
        $this->assertStringContainsString('Sitemap: https://webguard.marcel-breuer.dev/sitemap.xml', $robotsContent);
        $this->assertStringNotContainsString('webguard.m-breuer.dev', $robotsContent);
        $this->assertStringNotContainsString('www.webguard.marcel-breuer.dev', $robotsContent);
        $this->assertStringNotContainsString('Disallow:', $robotsContent);
    }

    public function test_www_host_redirects_to_non_www_canonical_host(): void
    {
        config(['app.url' => 'https://webguard.marcel-breuer.dev']);

        $testResponse = $this->get('https://www.webguard.marcel-breuer.dev/features?source=www');

        $this->assertSame(301, $testResponse->getStatusCode());
        $testResponse->assertRedirect('https://webguard.marcel-breuer.dev/features?source=www');
    }

    public function test_www_host_redirect_preserves_configured_canonical_port(): void
    {
        config(['app.url' => 'http://webguard.test:8080']);

        $testResponse = $this->get('http://www.webguard.test/features?source=www');

        $this->assertSame(301, $testResponse->getStatusCode());
        $testResponse->assertRedirect('http://webguard.test:8080/features?source=www');
    }

    public function test_non_canonical_www_hosts_are_not_redirected(): void
    {
        config(['app.url' => 'https://webguard.marcel-breuer.dev']);

        $testResponse = $this->get('https://www.example.test/features');

        $testResponse->assertOk();
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
        return SitemapPages::routeNames();
    }

    /**
     * @return list<string>
     */
    private function expectedIndexablePageUrls(): array
    {
        $urls = SitemapPages::urls();

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

    private function configureImprint(): void
    {
        config()->set('imprint.operator_name', 'Max Mustermann');
        config()->set('imprint.street', 'Musterstrasse 1');
        config()->set('imprint.postal_code', '10115');
        config()->set('imprint.city', 'Berlin');
        config()->set('imprint.country', 'Germany');
        config()->set('imprint.email', 'max@example.test');
        config()->set('imprint.phone', '+49 1512 3456789');
    }
}
