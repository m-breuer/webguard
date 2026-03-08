<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_user_can_switch_locale_and_persist_it_in_cookie(): void
    {
        $response = $this->from('/')->post(route('locale.switch'), [
            'locale' => SupportedLanguage::DE->value,
        ]);

        $response->assertRedirect('/');
        $response->assertCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value);

        $localizedPage = $this->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value)->get('/');
        $localizedPage->assertOk();
        $localizedPage->assertSee('lang="de"', false);
    }

    public function test_language_switch_is_visible_for_guests_in_top_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="language-switch-guest"', false);
    }

    public function test_language_switch_is_hidden_for_authenticated_users_in_top_navigation(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/monitorings');

        $response->assertOk();
        $response->assertDontSee('id="language-switch-desktop"', false);
        $response->assertDontSee('id="language-switch-mobile"', false);
    }

    public function test_authenticated_user_locale_in_database_overrides_cookie_value(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $response = $this->actingAs($user)
            ->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value)
            ->get('/monitorings');

        $response->assertOk();
        $response->assertSee('lang="en"', false);
    }

    public function test_authenticated_language_switch_updates_database_and_cookie(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $response = $this->actingAs($user)
            ->from('/monitorings')
            ->post(route('locale.switch'), ['locale' => SupportedLanguage::DE->value]);

        $response->assertRedirect('/monitorings');
        $response->assertCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value);

        $user->refresh();
        $this->assertSame(SupportedLanguage::DE->value, $user->locale);
    }

    public function test_accept_language_header_is_used_when_no_user_and_no_cookie(): void
    {
        $response = $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')->get('/');

        $response->assertOk();
        $response->assertSee('lang="de"', false);
    }

    public function test_cookie_has_priority_over_accept_language_for_anonymous_users(): void
    {
        $response = $this->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::EN->value)
            ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->get('/');

        $response->assertOk();
        $response->assertSee('lang="en"', false);
    }

    public function test_login_sets_locale_cookie_from_database_preference(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $response = $this->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value)
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect('/dashboard');
        $response->assertCookie(SupportedLanguage::cookieName(), SupportedLanguage::EN->value);
    }
}
