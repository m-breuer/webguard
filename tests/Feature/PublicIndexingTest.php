<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicIndexingTest extends TestCase
{
    public function test_marketing_and_legal_pages_are_not_served_by_core(): void
    {
        foreach (['/features', '/features/http-monitoring', '/monitoring-locations', '/imprint', '/terms-of-use', '/gdpr'] as $path) {
            $this->get($path)->assertNotFound();
        }
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
        $testResponse = $this->get(route('login'));

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

    public function test_sitemap_generation_commands_are_removed(): void
    {
        $commands = Artisan::all();

        $this->assertArrayNotHasKey('sitemap:generate', $commands);
        $this->assertArrayNotHasKey('robots:generate', $commands);
    }
}
