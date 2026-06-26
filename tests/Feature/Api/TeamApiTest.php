<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\TeamRole;
use App\Models\Package;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    public function test_team_api_exposes_visible_teams_and_admin_management(): void
    {
        Mail::fake();
        Package::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        $testResponse = $this->actingAs($admin)->postJson('/api/v1/teams', [
            'name' => 'API Team',
            'description' => 'Managed through API',
        ]);

        $testResponse->assertCreated()->assertJsonPath('data.name', 'API Team');
        $team = Team::query()->where('name', 'API Team')->firstOrFail();

        $team->memberships()->create([
            'user_id' => $member->id,
            'role' => TeamRole::MEMBER,
        ]);

        $this->actingAs($member)->getJson('/api/v1/teams')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Team']);

        $this->actingAs($outsider)->getJson('/api/v1/teams/' . $team->id)
            ->assertNotFound();

        $teamMembership = $team->memberships()->where('user_id', $member->id)->firstOrFail();

        $this->actingAs($member)->patchJson('/api/v1/teams/' . $team->id . '/members/' . $teamMembership->id, [
            'role' => TeamRole::ADMIN->value,
        ])->assertForbidden();

        $this->actingAs($admin)->patchJson('/api/v1/teams/' . $team->id . '/members/' . $teamMembership->id, [
            'role' => TeamRole::ADMIN->value,
        ])->assertOk()->assertJsonPath('data.role', TeamRole::ADMIN->value);

        $this->actingAs($admin)->postJson('/api/v1/teams/' . $team->id . '/invitations', [
            'email' => 'api-invite@example.com',
            'role' => TeamRole::MEMBER->value,
        ])->assertCreated();
    }

    public function test_team_api_allows_admin_to_revoke_pending_invitation(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $teamInvitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'api-pending@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'api-pending-token'),
            'invited_by_user_id' => $admin->id,
            'expires_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->deleteJson('/api/v1/teams/' . $team->id . '/invitations/' . $teamInvitation->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('team_invitations', ['id' => $teamInvitation->id]);
    }

    public function test_team_api_member_routes_reject_memberships_from_another_team(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $otherMember = User::factory()->create();

        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);

        $otherTeam = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $otherMembership = $otherTeam->memberships()->create([
            'user_id' => $otherMember->id,
            'role' => TeamRole::MEMBER,
        ]);

        $this->actingAs($admin)->patchJson('/api/v1/teams/' . $team->id . '/members/' . $otherMembership->id, [
            'role' => TeamRole::ADMIN->value,
        ])->assertNotFound();

        $this->actingAs($admin)->deleteJson('/api/v1/teams/' . $team->id . '/members/' . $otherMembership->id)
            ->assertNotFound();

        $this->assertDatabaseHas('team_memberships', [
            'id' => $otherMembership->id,
            'team_id' => $otherTeam->id,
            'role' => TeamRole::MEMBER->value,
        ]);
    }
}
