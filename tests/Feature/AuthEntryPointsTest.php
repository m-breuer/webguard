<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AuthEntryPointsTest extends TestCase
{
    public function test_login_page_uses_unified_auth_view_with_login_initial_mode(): void
    {
        $testResponse = $this->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-initial-mode="login"');
        $testResponse->assertSeeText(__('auth.auth_switch.login'));
        $testResponse->assertSeeText(__('auth.auth_switch.register'));
        $testResponse->assertSeeText(__('auth.auth_switch.demo'));
    }

    public function test_login_page_shows_auth_form_before_access_switch_on_mobile(): void
    {
        $testResponse = $this->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('class="order-2 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900 lg:order-1"');
        $testResponse->assertSeeHtml('class="order-1 rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800 lg:order-2"');
    }

    public function test_register_route_opens_unified_auth_view_in_register_mode(): void
    {
        $testResponse = $this->get(route('register'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-initial-mode="register"');
    }

    public function test_auth_entry_pages_are_not_crawlable_and_not_publicly_cacheable(): void
    {
        foreach (['login', 'register'] as $routeName) {
            $testResponse = $this->get(route($routeName));

            $testResponse->assertOk();
            $testResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');

            $cacheControl = (string) $testResponse->headers->get('Cache-Control');

            $this->assertStringContainsString('private', $cacheControl);
            $this->assertStringNotContainsString('public', $cacheControl);
        }
    }

    public function test_legacy_guest_query_opens_unified_auth_view_in_demo_mode(): void
    {
        $testResponse = $this->get(route('login', ['guest' => 'true']));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-initial-mode="demo"');
    }

    public function test_demo_route_is_removed(): void
    {
        $this->get('/demo')->assertNotFound();
    }

    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login', absolute: false));
    }

    public function test_register_mode_contains_combined_legal_consent_checkbox(): void
    {
        $testResponse = $this->get(route('login', ['mode' => 'register']));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('id="register_terms"');
        $testResponse->assertSeeHtml('name="terms"');
        $testResponse->assertSeeHtml('id="register_captcha"');
        $testResponse->assertSeeHtml('name="captcha"');
        $testResponse->assertSeeHtml(url('captcha/register'));
        $testResponse->assertSeeHtml('http://localhost:4321/terms-of-use');
        $testResponse->assertSeeHtml('http://localhost:4321/gdpr');
    }

    public function test_register_mode_uses_configured_landing_page_legal_links(): void
    {
        config()->set('app.marketing_url', 'https://marketing.example.test');

        $testResponse = $this->get(route('login', ['mode' => 'register']));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('href="https://marketing.example.test/terms-of-use" target="_blank" rel="noopener"');
        $testResponse->assertSeeHtml('href="https://marketing.example.test/gdpr" target="_blank" rel="noopener"');
    }

    public function test_register_captcha_image_is_served_locally(): void
    {
        $testResponse = $this->get(url('captcha/register'));

        $testResponse->assertOk();
        $testResponse->assertHeader('Content-Type', 'image/jpeg');
    }
}
