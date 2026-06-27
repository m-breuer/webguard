<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Package;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamApiCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_team_api_show_update_destroy_member_index_and_member_destroy_paths(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create([
            'name' => 'API Coverage',
            'created_by_user_id' => $admin->id,
        ]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $membership = $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'pending-api@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'pending-token'),
            'invited_by_user_id' => $admin->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($member)->getJson('/api/v1/teams/' . $team->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'API Coverage');
        $this->actingAs($member)->getJson('/api/v1/teams/' . $team->id . '/members')
            ->assertOk()
            ->assertJsonFragment(['email' => $member->email]);
        $this->actingAs($admin)->getJson('/api/v1/teams/' . $team->id . '/invitations')
            ->assertOk()
            ->assertJsonFragment(['email' => 'pending-api@example.com']);
        $this->actingAs($admin)->patchJson('/api/v1/teams/' . $team->id, [
            'name' => 'API Updated',
            'description' => 'Changed',
        ])->assertOk()->assertJsonPath('data.name', 'API Updated');
        $this->actingAs($admin)->deleteJson('/api/v1/teams/' . $team->id . '/members/' . $membership->id)
            ->assertNoContent();
        $this->assertDatabaseMissing('team_memberships', ['id' => $membership->id]);
        $this->actingAs($admin)->deleteJson('/api/v1/teams/' . $team->id)
            ->assertNoContent();
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_team_api_demo_user_write_paths_are_forbidden(): void
    {
        $demoUser = User::factory()->create(['role' => UserRole::DEMO]);
        $team = Team::factory()->create(['created_by_user_id' => $demoUser->id]);
        $membership = $team->memberships()->create(['user_id' => $demoUser->id, 'role' => TeamRole::ADMIN]);

        $this->actingAs($demoUser)->postJson('/api/v1/teams', [
            'name' => 'Blocked',
        ])->assertForbidden();
        $this->actingAs($demoUser)->patchJson('/api/v1/teams/' . $team->id, [
            'name' => 'Blocked',
        ])->assertForbidden();
        $this->actingAs($demoUser)->deleteJson('/api/v1/teams/' . $team->id)->assertForbidden();
        $this->actingAs($demoUser)->patchJson('/api/v1/teams/' . $team->id . '/members/' . $membership->id, [
            'role' => TeamRole::MEMBER->value,
        ])->assertForbidden();
        $this->actingAs($demoUser)->deleteJson('/api/v1/teams/' . $team->id . '/members/' . $membership->id)
            ->assertForbidden();
    }
}
