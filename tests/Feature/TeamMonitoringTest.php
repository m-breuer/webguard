<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\NotificationType;
use App\Enums\TeamRole;
use App\Mail\TeamInvitationMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationState;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamMonitoringTest extends TestCase
{
    public function test_user_can_create_team_and_invite_registered_or_unregistered_members(): void
    {
        Mail::fake();
        Package::factory()->create();
        $admin = User::factory()->create();
        $registeredUser = User::factory()->create(['email' => 'registered@example.com']);

        $testResponse = $this->actingAs($admin)->post(route('teams.store'), [
            'name' => 'Operations',
            'description' => 'Production monitoring',
        ]);

        $team = Team::query()->where('name', 'Operations')->firstOrFail();
        $testResponse->assertRedirect(route('teams.show', $team));
        $this->assertDatabaseHas('team_memberships', [
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'role' => TeamRole::ADMIN->value,
        ]);

        $this->actingAs($admin)->post(route('teams.invitations.store', $team), [
            'email' => $registeredUser->email,
            'role' => TeamRole::MEMBER->value,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('teams.invitations.store', $team), [
            'email' => 'new-user@example.com',
            'role' => TeamRole::ADMIN->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('team_invitations', ['team_id' => $team->id, 'email' => $registeredUser->email]);
        $this->assertDatabaseHas('team_invitations', ['team_id' => $team->id, 'email' => 'new-user@example.com']);
        Mail::assertSent(TeamInvitationMail::class, 2);
    }

    public function test_last_team_admin_cannot_leave_be_demoted_or_removed(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $membership = $team->memberships()->create([
            'user_id' => $admin->id,
            'role' => TeamRole::ADMIN,
        ]);

        $this->actingAs($admin)->delete(route('teams.leave', $team))
            ->assertSessionHasErrors('role');

        $this->actingAs($admin)->patch(route('teams.members.update', [$team, $membership]), [
            'role' => TeamRole::MEMBER->value,
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->delete(route('teams.members.destroy', [$team, $membership]))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('team_memberships', [
            'id' => $membership->id,
            'role' => TeamRole::ADMIN->value,
        ]);
    }

    public function test_team_admin_can_create_team_monitoring_and_member_can_only_view_it(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);

        $this->actingAs($admin)->post(route('monitorings.store'), [
            ...$this->monitoringPayload(),
            'team_id' => $team->id,
        ])->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->withoutGlobalScopes()->where('name', 'Team API')->firstOrFail();

        $this->assertNull($monitoring->user_id);
        $this->assertSame($team->id, $monitoring->team_id);

        $this->actingAs($member)->get(route('monitorings.show', $monitoring))->assertOk();
        $this->actingAs($member)->getJson('/api/v1/monitorings/' . $monitoring->id . '/status')->assertOk();
        $this->actingAs($member)->get(route('monitorings.edit', $monitoring))->assertForbidden();
        $this->actingAs($member)->delete(route('monitorings.destroy', $monitoring))->assertForbidden();
    }

    public function test_private_monitoring_can_move_to_team_and_back_to_private(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $monitoring = Monitoring::factory()->for($admin)->create();

        $this->actingAs($admin)->post(route('monitorings.team-ownership.store', $monitoring), [
            'team_id' => $team->id,
        ])->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();
        $this->assertNull($monitoring->user_id);
        $this->assertSame($team->id, $monitoring->team_id);

        $this->actingAs($admin)->delete(route('monitorings.team-ownership.destroy', $monitoring))
            ->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();
        $this->assertSame($admin->id, $monitoring->user_id);
        $this->assertNull($monitoring->team_id);
    }

    public function test_deleting_team_deletes_team_monitorings_but_keeps_private_monitorings(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $teamMonitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $admin->id,
        ]);
        $privateMonitoring = Monitoring::factory()->for($admin)->create();

        $this->actingAs($admin)->delete(route('teams.destroy', $team))->assertRedirect(route('teams.index'));

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('monitorings', ['id' => $teamMonitoring->id]);
        $this->assertDatabaseHas('monitorings', ['id' => $privateMonitoring->id]);
    }

    public function test_team_notification_read_state_is_independent_per_member(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        $monitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $admin->id,
        ]);

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => false,
            'sent' => true,
        ]);

        $this->actingAs($member)->post(route('notifications.markAsRead', $monitoringNotification))->assertRedirect();

        $this->assertNotNull(MonitoringNotificationState::query()
            ->where('monitoring_notification_id', $monitoringNotification->id)
            ->where('user_id', $member->id)
            ->value('read_at'));
        $this->assertNull(MonitoringNotificationState::query()
            ->where('monitoring_notification_id', $monitoringNotification->id)
            ->where('user_id', $admin->id)
            ->value('read_at'));
    }

    /**
     * @return array<string, mixed>
     */
    private function monitoringPayload(): array
    {
        return [
            'name' => 'Team API',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'preferred_location' => 'de-1',
            'public_label_enabled' => false,
            'notification_on_failure' => true,
            'notification_channels' => [],
            'failure_confirmation_threshold' => 2,
            'ssl_expiry_warning_days' => 7,
        ];
    }
}
