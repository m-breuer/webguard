<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ImprintPageTest extends TestCase
{
    public function test_imprint_page_is_publicly_available(): void
    {
        $this->configureImprint();

        $testResponse = $this->get(route('imprint'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Max Mustermann');
        $testResponse->assertSeeText('Musterstrasse 1');
        $testResponse->assertSeeText(__('imprint.actions.reveal_contact'));
        $testResponse->assertSeeText(__('imprint.contact_hidden'));
        $testResponse->assertDontSeeText('max@example.test');
        $testResponse->assertDontSeeText('+49 1512 3456789');
        $testResponse->assertSeeHtml('data-email-payload=');
        $testResponse->assertSeeHtml('data-phone-payload=');
    }

    public function test_impressum_route_redirects_to_imprint(): void
    {
        $testResponse = $this->get('/impressum');

        $testResponse->assertRedirect(route('imprint'));
    }

    public function test_imprint_page_is_included_in_sitemap(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml(route('imprint'));
    }

    public function test_footer_contains_imprint_link(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('imprint.footer_link'));
        $testResponse->assertSeeHtml(route('imprint'));
    }

    public function test_robots_txt_blocks_imprint_routes(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robotsContent);
        $this->assertStringContainsString('Disallow: /imprint', $robotsContent);
        $this->assertStringContainsString('Disallow: /impressum', $robotsContent);
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
