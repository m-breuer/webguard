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
        $testResponse = $this->from('/')->post(route('locale.switch'), [
            'locale' => SupportedLanguage::DE->value,
        ]);

        $testResponse->assertRedirect('/');
        $testResponse->assertCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value);

        $localizedPage = $this->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value)->get('/');
        $localizedPage->assertOk();
        $localizedPage->assertSeeHtml('lang="de"');
    }

    public function test_authenticated_user_locale_in_database_overrides_cookie_value(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $testResponse = $this->actingAs($user)
            ->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value)
            ->get('/monitorings');

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('lang="en"');
    }

    public function test_authenticated_language_switch_updates_database_and_cookie(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $testResponse = $this->actingAs($user)
            ->from('/monitorings')
            ->post(route('locale.switch'), ['locale' => SupportedLanguage::DE->value]);

        $testResponse->assertRedirect('/monitorings');
        $testResponse->assertCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value);

        $user->refresh();
        $this->assertSame(SupportedLanguage::DE->value, $user->locale);
    }

    public function test_accept_language_header_is_used_when_no_user_and_no_cookie(): void
    {
        $testResponse = $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('lang="de"');
    }

    public function test_cookie_has_priority_over_accept_language_for_anonymous_users(): void
    {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::EN->value)
            ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('lang="en"');
    }

    public function test_login_sets_locale_cookie_from_database_preference(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'locale' => SupportedLanguage::EN->value,
        ]);

        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), SupportedLanguage::DE->value)
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $testResponse->assertRedirect('/dashboard');
        $testResponse->assertCookie(SupportedLanguage::cookieName(), SupportedLanguage::EN->value);
    }
}
