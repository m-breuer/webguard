<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\SitemapPages;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicIndexingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('imprint.operator_name', 'Max Mustermann');
        config()->set('imprint.street', 'Musterstrasse 1');
        config()->set('imprint.postal_code', '10115');
        config()->set('imprint.city', 'Berlin');
        config()->set('imprint.country', 'Germany');
        config()->set('imprint.email', 'max@example.test');
        config()->set('imprint.phone', '+49 1512 3456789');
    }

    public function test_only_legal_pages_remain_in_the_core_sitemap(): void
    {
        $this->assertSame(['imprint', 'terms-of-use', 'gdpr'], SitemapPages::routeNames());

        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();

        foreach (SitemapPages::urls() as $url) {
            $testResponse->assertSeeHtml($url);
        }
    }

    public function test_legal_pages_remain_publicly_cacheable(): void
    {
        foreach (SitemapPages::routeNames() as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('public.cache', $route->gatherMiddleware());

            $testResponse = $this->get(route($routeName));
            $testResponse->assertOk();

            $cacheControl = (string) $testResponse->headers->get('Cache-Control');
            $this->assertStringContainsString('public', $cacheControl);
            $this->assertStringContainsString('max-age=300', $cacheControl);
            $this->assertStringContainsString('s-maxage=3600', $cacheControl);
        }
    }

    public function test_generated_sitemap_contains_exactly_the_legal_pages(): void
    {
        $sitemapPath = public_path('sitemap.xml');

        if (file_exists($sitemapPath)) {
            unlink($sitemapPath);
        }

        $this->artisan('sitemap:generate')->assertSuccessful();

        $sitemapContent = file_get_contents($sitemapPath);

        $this->assertIsString($sitemapContent);

        foreach (SitemapPages::urls() as $url) {
            $this->assertStringContainsString($url, $sitemapContent);
        }

        $this->assertSame(3, mb_substr_count($sitemapContent, '<url>'));

        unlink($sitemapPath);
    }

    public function test_application_robots_file_does_not_advertise_a_marketing_sitemap(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robotsContent);
        $this->assertStringContainsString('User-agent: *', $robotsContent);
        $this->assertStringNotContainsString('Sitemap:', $robotsContent);
    }

    public function test_removed_marketing_routes_are_not_served_by_core(): void
    {
        $this->get('/features')->assertNotFound();
        $this->get('/features/http-monitoring')->assertNotFound();
        $this->get('/monitoring-locations')->assertNotFound();
    }
}
