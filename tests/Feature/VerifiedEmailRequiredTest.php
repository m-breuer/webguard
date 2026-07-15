<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VerifiedEmailRequiredTest extends TestCase
{
    public function test_unverified_user_is_redirected_to_verification_notice_for_protected_routes(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($user)->get(route('dashboard'));
        $testResponse->assertRedirect(route('verification.notice'));

        $monitoringsResponse = $this->actingAs($user)->get(route('monitorings.index'));
        $monitoringsResponse->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_can_open_profile(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($user)->get(route('profile.edit'));

        $testResponse->assertOk();
    }

    public function test_unverified_user_can_update_own_profile(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create([
            'name' => 'Original Name',
            'email' => 'original@example.test',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
            'theme' => 'dark',
        ]);

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHas('success', __('profile.messages.profile_updated'));

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_unverified_user_can_update_own_password(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $testResponse->assertSessionHas('success', __('profile.form.saved'));
        $this->assertTrue(Hash::check('new-password', (string) $user->fresh()->password));
    }

    public function test_verified_user_can_access_dashboard_and_protected_routes(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->get(route('dashboard'));
        $testResponse->assertOk();
        $testResponse->assertSeeText(__('dashboard.title'));

        $monitoringsResponse = $this->actingAs($user)->get(route('monitorings.index'));
        $monitoringsResponse->assertOk();
    }

    public function test_unverified_user_can_open_verification_notice_page(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($user)->get(route('verification.notice'));

        $testResponse->assertOk();
    }
}
