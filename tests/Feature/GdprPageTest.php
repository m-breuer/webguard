<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class GdprPageTest extends TestCase
{
    public function test_gdpr_page_is_publicly_available_and_hides_contact_data_until_reveal(): void
    {
        $this->configureImprintContact();

        $testResponse = $this->get(route('gdpr'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('legal.privacy_policy.hero.title'));
        $testResponse->assertSeeText(__('imprint.actions.reveal_contact'));
        $testResponse->assertSeeText(__('imprint.contact_hidden'));
        $testResponse->assertDontSeeText('privacy@example.test');
        $testResponse->assertDontSeeText('+49 111 222333');
        $testResponse->assertSeeHtml('data-email-payload=');
        $testResponse->assertSeeHtml('data-phone-payload=');
        $testResponse->assertSeeHtml('<meta name="robots" content="noindex, nofollow">');
    }

    public function test_privacy_policy_route_redirects_to_gdpr(): void
    {
        $testResponse = $this->get('/privacy-policy');

        $testResponse->assertRedirect(route('gdpr'));
    }

    public function test_gdpr_page_is_included_in_sitemap(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml(route('gdpr'));
    }

    public function test_robots_txt_blocks_gdpr_routes(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robotsContent);
        $this->assertStringContainsString('Disallow: /gdpr', $robotsContent);
        $this->assertStringContainsString('Disallow: /privacy-policy', $robotsContent);
    }

    private function configureImprintContact(): void
    {
        config()->set('imprint.email', 'privacy@example.test');
        config()->set('imprint.phone', '+49 111 222333');
    }
}
