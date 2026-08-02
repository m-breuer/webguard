<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FormModalTeamsProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_create_and_edit_modal_fragments_are_available(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);

        $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('teams.create', ['modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('value="team-create"');

        $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('teams.edit', [$team, 'modal' => 1]))
            ->assertOk()
            ->assertSee($team->name)
            ->assertSeeHtml('value="team-edit"');
    }

    public function test_team_modal_validation_reopens_the_matching_modal_and_update_succeeds(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);

        $testResponse = $this->actingAs($admin)->post(route('teams.store'), [
            'description' => 'Missing name',
            'modal_form' => 'team-create',
        ]);

        $testResponse
            ->assertRedirect(route('teams.index', ['modal' => 'team-create']))
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->get(route('teams.index', ['modal' => 'team-create']))
            ->assertOk()
            ->assertSeeHtml('value="team-create"')
            ->assertSeeText(__('validation.required', ['attribute' => 'name']));

        $this->actingAs($admin)->patch(route('teams.update', $team), [
            'name' => 'Updated team',
            'description' => 'Updated description',
            'modal_form' => 'team-edit',
        ])->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated team',
            'description' => 'Updated description',
        ]);
    }

    public function test_profile_modal_fragments_and_profile_update_flow_are_available(): void
    {
        Package::factory()->create();
        $user = User::factory()->create(['theme' => 'system']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeHtml('id="profile-information"')
            ->assertSeeHtml('id="profile-password"')
            ->assertSeeHtml('id="profile-api"')
            ->assertSeeHtml('id="profile-delete"')
            ->assertDontSeeHtml('sm:grid-cols-2')
            ->assertDontSeeHtml('data-form-modal-name="profile-information-form-modal"')
            ->assertDontSeeHtml('data-form-modal-name="profile-password-form-modal"');

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('profile.edit', ['modal' => 'profile-information']))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('value="profile-information"')
            ->assertSeeText(__('profile.notification_settings.heading'));

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('profile.edit', ['modal' => 'profile-password']))
            ->assertOk()
            ->assertSeeHtml('value="profile-password"')
            ->assertSeeText(__('profile.update_password.heading'));

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated profile',
            'email' => $user->email,
            'theme' => 'dark',
            'modal_form' => 'profile-information',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated profile',
            'theme' => 'dark',
        ]);
    }

    public function test_profile_modal_validation_reopens_the_matching_password_modal_and_update_succeeds(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->from(route('profile.edit'))->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'modal_form' => 'profile-password',
        ]);

        $testResponse
            ->assertRedirect(route('profile.edit', ['modal' => 'profile-password']))
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($user)
            ->get(route('profile.edit', ['modal' => 'profile-password']))
            ->assertOk()
            ->assertSeeHtml('value="profile-password"')
            ->assertSeeText(__('profile.update_password.heading'));

        $this->actingAs($user)->from(route('profile.edit'))->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'modal_form' => 'profile-password',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('new-password', (string) $user->fresh()->password));
    }
}
