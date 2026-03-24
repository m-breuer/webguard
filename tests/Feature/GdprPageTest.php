<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureImprint();
    }

    public function test_gdpr_page_is_publicly_available(): void
    {
        $testResponse = $this->get(route('gdpr'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('gdpr.hero.title'));
    }

    public function test_datenschutz_route_redirects_to_gdpr(): void
    {
        $testResponse = $this->get('/datenschutz');

        $testResponse->assertRedirect(route('gdpr'));
    }

    public function test_gdpr_page_is_noindex_and_nofollow(): void
    {
        $testResponse = $this->get(route('gdpr'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('<meta name="robots" content="noindex, nofollow">');
    }

    public function test_gdpr_page_is_included_in_sitemap(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml(route('gdpr'));
    }

    public function test_footer_contains_gdpr_link(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('gdpr.footer_link'));
        $testResponse->assertSeeHtml(route('gdpr'));
    }

    public function test_robots_txt_blocks_gdpr_routes(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robotsContent);
        $this->assertStringContainsString('Disallow: /gdpr', $robotsContent);
        $this->assertStringContainsString('Disallow: /datenschutz', $robotsContent);
    }

    public function test_accept_language_header_is_used_for_gdpr_page(): void
    {
        $testResponse = $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')
            ->get(route('gdpr'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('lang="de"');
        $testResponse->assertSeeText('Datenschutzerklaerung');
    }

    public function test_authenticated_user_locale_from_profile_has_priority_on_gdpr_page(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $testResponse = $this->actingAs($user)
            ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->get(route('gdpr'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('lang="en"');
        $testResponse->assertSeeText('Privacy Policy');
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
