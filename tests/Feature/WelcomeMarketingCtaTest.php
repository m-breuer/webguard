<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use Tests\TestCase;

class WelcomeMarketingCtaTest extends TestCase
{
    public function test_german_welcome_header_uses_start_and_demo_ctas(): void
    {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), 'de')->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Jetzt starten');
        $testResponse->assertSeeText('Demo-Zugang');
        $testResponse->assertSeeHtml('href="' . route('register') . '"');
        $testResponse->assertSeeHtml('href="' . route('login', ['mode' => 'demo']) . '"');
        $testResponse->assertDontSeeText('API-Doku lesen');
        $testResponse->assertDontSeeText('Kostenfrei starten');
        $testResponse->assertDontSeeText('Demo-Zugang nutzen');
    }

    public function test_operation_metric_uses_fixed_five_minute_interval_copy(): void
    {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), 'de')->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('24/7 Checks alle 5 Minuten');
        $testResponse->assertDontSeeText('flexiblen Intervallen');
    }
}
