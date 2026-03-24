<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class UserEmailVerificationFromEditPageTest extends TestCase
{
    public function test_admin_edit_page_shows_verify_email_action_for_unverified_user(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($admin)->get(route('admin.users.edit', $user));

        $testResponse->assertOk();
        $testResponse->assertSee(__('user.actions.verify_email'));
        $testResponse->assertSeeHtml('action="' . route('admin.users.verify', $user) . '"');
    }

    public function test_admin_edit_page_hides_verify_email_action_for_verified_user(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user = User::factory()->create();

        $testResponse = $this->actingAs($admin)->get(route('admin.users.edit', $user));

        $testResponse->assertOk();
        $testResponse->assertDontSee(__('user.actions.verify_email'));
    }

    public function test_admin_can_verify_user_email_from_edit_page_action(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($admin)->post(route('admin.users.verify', $user));

        $testResponse->assertRedirect(route('admin.users.edit', $user));
        $testResponse->assertSessionHas('success', __('user.messages.user_verified'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
}
