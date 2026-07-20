<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FooterMarkupTest extends TestCase
{
    public function test_footer_renders_core_links_and_optional_marketing_link(): void
    {
        config()->set('app.marketing_url', 'https://marketing.example.test');

        $this->configureImprint();

        $testResponse = $this->get(route('imprint'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('class="w-full sm:w-auto"');
        $testResponse->assertSeeHtml('flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:justify-end');
        $testResponse->assertSeeHtml('https://marketing.example.test');
        $testResponse->assertSeeHtml(route('imprint'));
        $testResponse->assertSeeHtml(route('terms-of-use'));
        $testResponse->assertSeeHtml(route('gdpr'));
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
