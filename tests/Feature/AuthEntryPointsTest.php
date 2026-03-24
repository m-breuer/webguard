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

    public function test_register_route_redirects_to_login_with_register_mode(): void
    {
        $testResponse = $this->get(route('register'));

        $testResponse->assertRedirect(route('login', ['mode' => 'register']));

        $resolvedResponse = $this->followingRedirects()->get(route('register'));

        $resolvedResponse->assertOk();
        $resolvedResponse->assertSeeHtml('data-initial-mode="register"');
    }

    public function test_guest_query_opens_unified_auth_view_in_demo_mode(): void
    {
        $testResponse = $this->get(route('login', ['guest' => 'true']));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-initial-mode="demo"');
    }

    public function test_demo_route_is_removed(): void
    {
        $this->get('/demo')->assertNotFound();
    }

    public function test_welcome_secondary_ctas_point_to_guest_login(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('href="' . route('login', ['guest' => 'true']) . '"');
    }
}
