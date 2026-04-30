<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class TermsOfUsePageTest extends TestCase
{
    public function test_terms_of_use_page_is_publicly_available(): void
    {
        $this->configureImprintContact();

        $testResponse = $this->get(route('terms-of-use'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('legal.terms_of_use.hero.title'));
        $testResponse->assertSeeText(__('legal.terms_of_use.sections.scope.title'));
        $testResponse->assertSeeText(__('legal.terms_of_use.sections.non_commercial.title'));
        $testResponse->assertSeeText(__('imprint.actions.reveal_contact'));
        $testResponse->assertSeeText(__('imprint.contact_hidden'));
        $testResponse->assertDontSeeText('legal@example.test');
        $testResponse->assertSeeHtml('data-email-payload=');
        $testResponse->assertSeeHtml('data-phone-payload=');
        $testResponse->assertSeeHtml('<meta name="robots" content="noindex, nofollow">');
    }

    public function test_terms_of_use_describe_current_monitoring_and_notification_features(): void
    {
        $this->configureImprintContact();

        foreach (['de-DE' => 'de', 'en-US' => 'en'] as $acceptLanguage => $locale) {
            $testResponse = $this->withHeader('Accept-Language', $acceptLanguage)
                ->get(route('terms-of-use'));

            $scopeItems = (array) trans('legal.terms_of_use.sections.scope.items', [], $locale);
            $obligationItems = (array) trans('legal.terms_of_use.sections.obligations.items', [], $locale);

            $testResponse->assertOk();
            $testResponse->assertSeeText($scopeItems[0]);
            $testResponse->assertSeeText($scopeItems[1]);
            $testResponse->assertSeeText($scopeItems[2]);
            $testResponse->assertSeeText($scopeItems[4]);
            $testResponse->assertSeeText($scopeItems[5]);
            $testResponse->assertSeeText($scopeItems[6]);
            $testResponse->assertSeeText($scopeItems[7]);
            $testResponse->assertSeeText($obligationItems[2]);
            $testResponse->assertSeeText($obligationItems[6]);
        }
    }

    public function test_agb_route_redirects_to_terms_of_use(): void
    {
        $testResponse = $this->get('/agb');

        $testResponse->assertRedirect(route('terms-of-use'));
    }

    public function test_terms_of_use_page_is_included_in_sitemap(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml(route('terms-of-use'));
    }

    public function test_footer_contains_terms_of_use_link(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('legal.terms_of_use.footer_link'));
        $testResponse->assertSeeHtml(route('terms-of-use'));
    }

    public function test_robots_txt_blocks_terms_of_use_routes(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robotsContent);
        $this->assertStringContainsString('Disallow: /terms-of-use', $robotsContent);
        $this->assertStringContainsString('Disallow: /agb', $robotsContent);
    }

    private function configureImprintContact(): void
    {
        config()->set('imprint.email', 'legal@example.test');
        config()->set('imprint.phone', '+49 1512 3456789');
    }
}
