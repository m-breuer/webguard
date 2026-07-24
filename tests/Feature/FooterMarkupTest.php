<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FooterMarkupTest extends TestCase
{
    public function test_footer_renders_core_links_and_optional_marketing_link(): void
    {
        config()->set('app.marketing_url', 'https://marketing.example.test');
        config()->set('app.marketing_legal_url');

        $this->configureImprint();

        $testResponse = $this->get(route('imprint'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('class="w-full sm:w-auto"');
        $testResponse->assertSeeHtml('flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:justify-end');
        $testResponse->assertSeeHtml('https://marketing.example.test');
        $testResponse->assertSeeHtml('target="_blank" rel="noopener"');
        $testResponse->assertSeeHtml(route('imprint'));
        $testResponse->assertSeeHtml(route('terms-of-use'));
        $testResponse->assertSeeHtml(route('gdpr'));
    }

    public function test_footer_uses_external_marketing_legal_links_when_configured(): void
    {
        config()->set('app.marketing_legal_url', 'https://marketing.example.test/legal/');

        $this->configureImprint();

        $testResponse = $this->get(route('imprint'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('href="https://marketing.example.test/legal/imprint"');
        $testResponse->assertSeeHtml('href="https://marketing.example.test/legal/terms-of-use"');
        $testResponse->assertSeeHtml('href="https://marketing.example.test/legal/gdpr"');
        $testResponse->assertSeeHtml('target="_blank" rel="noopener"');
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
