<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\TeamRole;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class InternalUiTeamWorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_team_members_and_invitations_while_members_cannot(): void
    {
        Mail::fake();
        Package::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        TeamMembership::factory()->for($team)->for($admin)->admin()->create();
        $membership = TeamMembership::factory()->for($team)->for($member)->create();

        $this->actingAs($member)->getJson(route('app.teams.show', $team))->assertOk()->assertJsonPath('data.can_manage', false);
        $this->actingAs($member)->postJson(route('app.teams.invitations.store', $team), ['email' => 'new@example.test', 'role' => 'member'])->assertForbidden();
        $this->actingAs($admin)->patchJson(route('app.teams.members.update', [$team, $membership]), ['role' => TeamRole::ADMIN->value])->assertOk()->assertJsonPath('data.members.1.role', 'admin');
        $this->actingAs($admin)->postJson(route('app.teams.invitations.store', $team), ['email' => 'new@example.test', 'role' => TeamRole::MEMBER->value])->assertCreated()->assertJsonPath('data.invitations.0.email', 'new@example.test');
    }

    public function test_member_can_leave_but_last_admin_is_protected(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        TeamMembership::factory()->for($team)->for($admin)->admin()->create();
        TeamMembership::factory()->for($team)->for($member)->create();
        $this->actingAs($member)->deleteJson(route('app.teams.leave', $team))->assertOk()->assertJsonPath('data.left', true);
        $this->assertDatabaseMissing('team_memberships', ['team_id' => $team->id, 'user_id' => $member->id]);
        $this->actingAs($admin)->deleteJson(route('app.teams.leave', $team))->assertUnprocessable()->assertJsonValidationErrors(['role']);
    }

    public function test_only_an_admin_who_confirms_the_team_name_can_delete_a_team(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Checkout', 'created_by_user_id' => $admin->id]);
        TeamMembership::factory()->for($team)->for($admin)->admin()->create();
        TeamMembership::factory()->for($team)->for($member)->create();
        $team->invitations()->create([
            'email' => 'pending@example.test',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'pending-team-deletion'),
            'invited_by_user_id' => $admin->id,
            'expires_at' => now()->addDay(),
        ]);
        $monitoring = Monitoring::factory()->for($team)->create();
        $group = MonitoringGroup::factory()->for($admin)->create();
        $group->monitorings()->attach($monitoring);
        $statusPage = StatusPage::query()->create(['user_id' => $admin->id, 'name' => 'Checkout status', 'is_public' => true]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'Checkout', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($monitoring, ['position' => 0]);

        $this->actingAs($member)
            ->deleteJson(route('app.teams.destroy', $team), ['confirmation' => $team->name])
            ->assertForbidden();
        $this->actingAs($admin)
            ->deleteJson(route('app.teams.destroy', $team), ['confirmation' => 'Wrong name'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');
        $this->actingAs($admin)
            ->deleteJson(route('app.teams.destroy', $team), ['confirmation' => $team->name])
            ->assertNoContent();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('team_memberships', ['team_id' => $team->id]);
        $this->assertDatabaseMissing('team_invitations', ['team_id' => $team->id]);
        $this->assertDatabaseMissing('monitorings', ['id' => $monitoring->id]);
        $this->assertDatabaseHas('monitoring_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('status_pages', ['id' => $statusPage->id]);
        $this->assertDatabaseHas('status_page_components', ['id' => $statusPageComponent->id]);
        $this->assertDatabaseMissing('monitoring_group_monitoring', ['monitoring_id' => $monitoring->id]);
        $this->assertDatabaseMissing('status_page_component_monitoring', ['monitoring_id' => $monitoring->id]);
    }
}
