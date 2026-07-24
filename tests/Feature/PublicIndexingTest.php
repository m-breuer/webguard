<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
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

    public function test_robots_txt_disallows_all_crawlers(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertSame("User-agent: *\nDisallow: /\n", $robotsContent);
    }

    public function test_sitemap_route_and_file_are_removed(): void
    {
        $this->assertFalse(Route::has('sitemap'));
        $this->get('/sitemap.xml')->assertNotFound();
        $this->assertFileDoesNotExist(public_path('sitemap.xml'));
    }

    public function test_application_pages_send_a_global_no_crawl_header_without_seo_markup(): void
    {
        foreach (['login', 'imprint', 'terms-of-use', 'gdpr'] as $routeName) {
            $testResponse = $this->get(route($routeName));

            $testResponse->assertOk();
            $testResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
            $testResponse->assertDontSeeHtml('<meta name="robots"');
            $testResponse->assertDontSeeHtml('<meta name="description"');
            $testResponse->assertDontSeeHtml('<meta name="keywords"');
            $testResponse->assertDontSeeHtml('property="og:');
            $testResponse->assertDontSeeHtml('name="twitter:');
            $testResponse->assertDontSeeHtml('<link rel="canonical"');
            $testResponse->assertDontSeeHtml('application/ld+json');
        }
    }

    public function test_sitemap_generation_commands_are_removed(): void
    {
        $commands = Artisan::all();

        $this->assertArrayNotHasKey('sitemap:generate', $commands);
        $this->assertArrayNotHasKey('robots:generate', $commands);
    }

    public function test_removed_marketing_routes_are_not_served_by_core(): void
    {
        $this->get('/features')->assertNotFound();
        $this->get('/features/http-monitoring')->assertNotFound();
        $this->get('/monitoring-locations')->assertNotFound();
    }
}
