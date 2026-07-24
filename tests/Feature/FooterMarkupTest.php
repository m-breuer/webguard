<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\LegalLinks;
use Tests\TestCase;

class FooterMarkupTest extends TestCase
{
    public function test_footer_links_to_the_marketing_site_and_its_legal_pages(): void
    {
        config()->set('app.marketing_url', 'https://marketing.example.test');

        $testResponse = $this->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('https://marketing.example.test');
        $testResponse->assertSeeHtml(LegalLinks::imprint());
        $testResponse->assertSeeHtml(LegalLinks::termsOfUse());
        $testResponse->assertSeeHtml(LegalLinks::privacyPolicy());
    }
}
