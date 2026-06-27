<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Package;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamControllerCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_team_pages_render_index_create_show_and_edit(): void
    {
        $admin = User::factory()->create();
        $team = Team::factory()->create([
            'name' => 'Coverage Team',
            'created_by_user_id' => $admin->id,
        ]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'pending@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'pending-token'),
            'invited_by_user_id' => $admin->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)->get(route('teams.index'))
            ->assertOk()
            ->assertSeeText('Coverage Team');
        $this->actingAs($admin)->get(route('teams.create'))->assertOk();
        $this->actingAs($admin)->get(route('teams.show', $team))
            ->assertOk()
            ->assertSeeText('pending@example.com');
        $this->actingAs($admin)->get(route('teams.edit', $team))
            ->assertOk()
            ->assertSeeText('Coverage Team');

        $member = User::factory()->create();
        $membership = $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        $this->actingAs($admin)->patch(route('teams.members.update', [$team, $membership]), [
            'role' => TeamRole::ADMIN->value,
        ])->assertRedirect();
        $this->actingAs($admin)->delete(route('teams.members.destroy', [$team, $membership]))
            ->assertRedirect();
        $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        $this->actingAs($member)->delete(route('teams.leave', $team))
            ->assertRedirect(route('teams.index'));
    }

    public function test_team_update_destroy_and_invitation_accept_controller_paths(): void
    {
        $admin = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create([
            'name' => 'Old Name',
            'created_by_user_id' => $admin->id,
        ]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);

        $this->actingAs($admin)->patch(route('teams.update', $team), [
            'name' => 'New Name',
            'description' => 'Updated',
        ])->assertRedirect(route('teams.show', $team));
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'New Name']);

        TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'accept-token'),
            'invited_by_user_id' => $admin->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->app['auth']->guard()->logout();

        $this->get(route('team-invitations.accept', 'accept-token'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('team.messages.login_to_accept'));

        $this->actingAs($invitedUser)->get(route('team-invitations.accept', 'accept-token'))
            ->assertRedirect(route('teams.show', $team));
        $this->assertDatabaseHas('team_memberships', [
            'team_id' => $team->id,
            'user_id' => $invitedUser->id,
        ]);

        $this->actingAs($admin)->delete(route('teams.destroy', $team))
            ->assertRedirect(route('teams.index'));
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_invitation_accept_redirects_new_users_to_prefilled_registration(): void
    {
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'new-person@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'register-token'),
            'invited_by_user_id' => $admin->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->get(route('team-invitations.accept', 'register-token'))
            ->assertRedirect(route('register', ['mode' => 'register', 'email' => 'new-person@example.com']));
    }
}
