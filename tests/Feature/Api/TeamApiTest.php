<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\TeamRole;
use App\Models\Package;
use App\Models\Team;
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

        $createResponse = $this->actingAs($admin)->postJson('/api/v1/teams', [
            'name' => 'API Team',
            'description' => 'Managed through API',
        ]);

        $createResponse->assertCreated()->assertJsonPath('data.name', 'API Team');
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

        $membership = $team->memberships()->where('user_id', $member->id)->firstOrFail();

        $this->actingAs($member)->patchJson('/api/v1/teams/' . $team->id . '/members/' . $membership->id, [
            'role' => TeamRole::ADMIN->value,
        ])->assertForbidden();

        $this->actingAs($admin)->patchJson('/api/v1/teams/' . $team->id . '/members/' . $membership->id, [
            'role' => TeamRole::ADMIN->value,
        ])->assertOk()->assertJsonPath('data.role', TeamRole::ADMIN->value);

        $this->actingAs($admin)->postJson('/api/v1/teams/' . $team->id . '/invitations', [
            'email' => 'api-invite@example.com',
            'role' => TeamRole::MEMBER->value,
        ])->assertCreated();
    }
}
