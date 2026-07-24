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
        $testResponse->assertSeeText(__('imprint.sections.disclaimer'));
        $testResponse->assertSeeText(__('imprint.disclaimer'));
        $testResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $testResponse->assertSeeHtml('rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10');
    }

    public function test_impressum_route_redirects_to_imprint(): void
    {
        $testResponse = $this->get('/impressum');

        $testResponse->assertRedirect(route('imprint'));
    }

    public function test_footer_contains_imprint_link(): void
    {
        $this->configureImprint();

        $testResponse = $this->get(route('imprint'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('imprint.footer_link'));
        $testResponse->assertSeeHtml(route('imprint'));
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
