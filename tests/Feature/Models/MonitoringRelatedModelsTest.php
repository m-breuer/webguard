<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\IncidentUpdateStatus;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Enums\NotificationType;
use App\Enums\TeamRole;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Monitoring;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationPreference;
use App\Models\MonitoringNotificationState;
use App\Models\MonitoringResponse;
use App\Models\MonitoringSslResult;
use App\Models\Package;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Notifications\MonitoringNotificationStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringRelatedModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_domain_and_ssl_results_expose_monitoring_user_relations_and_casts(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $domainResult = MonitoringDomainResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'expires_at' => '2026-07-27 00:00:00',
            'is_valid' => 1,
            'registrar' => 'Example Registrar',
            'checked_at' => '2026-06-27 00:00:00',
        ]);
        $sslResult = MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'expires_at' => '2026-07-27 00:00:00',
            'is_valid' => 0,
            'issuer' => 'Example CA',
            'issued_at' => '2026-01-27 00:00:00',
        ]);

        $this->assertTrue($domainResult->is_valid);
        $this->assertSame($monitoring->id, $domainResult->monitoring->id);
        $this->assertSame($user->id, $domainResult->user->id);
        $this->assertFalse($sslResult->is_valid);
        $this->assertSame($monitoring->id, $sslResult->monitoring->id);
        $this->assertSame($user->id, $sslResult->user->id);
    }

    public function test_team_invitation_pending_state_scope_relations_and_casts(): void
    {
        $inviter = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $inviter->id]);
        $pendingInvitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'pending@example.com',
            'role' => TeamRole::ADMIN,
            'token_hash' => hash('sha256', 'pending-token'),
            'invited_by_user_id' => $inviter->id,
            'expires_at' => now()->addDay(),
        ]);
        $expiredInvitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'expired@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'expired-token'),
            'invited_by_user_id' => $inviter->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($pendingInvitation->isPending());
        $this->assertFalse($expiredInvitation->isPending());
        $this->assertSame(TeamRole::ADMIN, $pendingInvitation->role);
        $this->assertSame($team->id, $pendingInvitation->team->id);
        $this->assertSame($inviter->id, $pendingInvitation->invitedBy->id);
        $this->assertTrue(TeamInvitation::query()->pending()->whereKey($pendingInvitation->id)->exists());
        $this->assertFalse(TeamInvitation::query()->pending()->whereKey($expiredInvitation->id)->exists());
    }

    public function test_monitoring_notification_states_track_read_state_and_service_updates(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $notification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is up',
            'read' => false,
            'sent' => false,
        ]);

        /** @var MonitoringNotificationState $state */
        $state = MonitoringNotificationState::query()
            ->where('monitoring_notification_id', $notification->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame($notification->id, $state->monitoringNotification->id);
        $this->assertSame($user->id, $state->user->id);
        $this->assertFalse($state->isRead());

        app(MonitoringNotificationStateService::class)->markRead($notification, $user);
        $this->assertTrue($state->fresh()->isRead());

        $state->forceFill(['read_at' => now()])->save();

        $this->assertTrue($state->fresh()->isRead());
    }

    public function test_monitoring_response_preference_and_incident_update_relations_and_casts(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $response = MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => '200',
            'response_time' => '123.45',
            'server_health_metrics' => ['cpu' => 20],
        ]);
        $preference = MonitoringNotificationPreference::query()->create([
            'monitoring_id' => $monitoring->id,
            'user_id' => $user->id,
            'notification_on_failure' => true,
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 45,
        ]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => now()->subHour(),
            'up_at' => now(),
        ]);
        $incidentUpdate = IncidentUpdate::query()->create([
            'incident_id' => $incident->id,
            'status' => IncidentUpdateStatus::RESOLVED,
            'message' => 'Recovered',
        ]);

        $this->assertSame(MonitoringStatus::UP, $response->status);
        $this->assertSame(200, $response->http_status_code);
        $this->assertSame(123.45, $response->response_time);
        $this->assertSame(['cpu' => 20], $response->server_health_metrics);
        $this->assertSame($monitoring->id, $response->monitoring->id);
        $this->assertSame($user->id, $response->user->id);

        $this->assertTrue($preference->notification_on_failure);
        $this->assertSame(['mail'], $preference->notification_channels);
        $this->assertSame(45, $preference->ssl_expiry_warning_days);
        $this->assertSame($monitoring->id, $preference->monitoring->id);
        $this->assertSame($user->id, $preference->user->id);

        $this->assertSame(IncidentUpdateStatus::RESOLVED, $incidentUpdate->status);
        $this->assertSame($incident->id, $incidentUpdate->incident->id);
    }

    public function test_monitoring_ownership_visibility_maintenance_locations_scopes_and_activity_options(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);

        $privateMonitoring = Monitoring::factory()->for($owner)->create([
            'type' => MonitoringType::HEARTBEAT,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => 'de-1',
            'preferred_locations' => ['de-1', 'us-1', ''],
            'maintenance_from' => now()->subMinute(),
            'maintenance_until' => null,
        ]);
        $teamMonitoring = Monitoring::factory()->serverHealth()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $admin->id,
            'status' => MonitoringLifecycleStatus::PAUSED,
            'preferred_location' => 'us-1',
            'preferred_locations' => ['us-1'],
            'maintenance_from' => now()->subHour(),
            'maintenance_until' => now()->addHour(),
        ]);
        Monitoring::factory()->for($outsider)->create([
            'preferred_location' => 'nl-1',
            'preferred_locations' => ['nl-1'],
        ]);

        $this->assertTrue($privateMonitoring->isActive());
        $this->assertFalse($privateMonitoring->isPaused());
        $this->assertTrue($privateMonitoring->isHeartbeat());
        $this->assertFalse($privateMonitoring->isServerHealth());
        $this->assertTrue($privateMonitoring->isPrivateOwned());
        $this->assertFalse($privateMonitoring->isTeamOwned());
        $this->assertTrue($privateMonitoring->isVisibleTo($owner));
        $this->assertFalse($privateMonitoring->isVisibleTo($outsider));
        $this->assertTrue($privateMonitoring->isManageableBy($owner));
        $this->assertFalse($privateMonitoring->isManageableBy($outsider));
        $this->assertTrue($privateMonitoring->isUnderMaintenance());
        $this->assertSame(['de-1', 'us-1'], $privateMonitoring->preferredLocationCodes());

        $this->assertTrue($teamMonitoring->isPaused());
        $this->assertTrue($teamMonitoring->isServerHealth());
        $this->assertTrue($teamMonitoring->isTeamOwned());
        $this->assertFalse($teamMonitoring->isPrivateOwned());
        $this->assertTrue($teamMonitoring->isVisibleTo($member));
        $this->assertTrue($teamMonitoring->isManageableBy($admin));
        $this->assertFalse($teamMonitoring->isManageableBy($member));
        $this->assertTrue($teamMonitoring->isUnderMaintenance());

        $this->assertTrue(Monitoring::query()->withoutGlobalScope('user')->active()->whereKey($privateMonitoring->id)->exists());
        $this->assertTrue(Monitoring::query()->withoutGlobalScope('user')->paused()->whereKey($teamMonitoring->id)->exists());
        $this->assertTrue(Monitoring::query()->withoutGlobalScope('user')->visibleTo($member)->whereKey($teamMonitoring->id)->exists());
        $this->assertTrue(Monitoring::query()->withoutGlobalScope('user')->manageableBy($admin)->whereKey($teamMonitoring->id)->exists());
        $this->assertTrue(Monitoring::query()->withoutGlobalScope('user')->privateOwnedBy($owner)->whereKey($privateMonitoring->id)->exists());
        $this->assertTrue(Monitoring::query()->withoutGlobalScope('user')->assignedToLocation('us-1')->whereKey($teamMonitoring->id)->exists());
        $activityLogOptions = $privateMonitoring->getActivitylogOptions();

        $this->assertSame('monitoring', $activityLogOptions->logName);
        $this->assertSame('monitoring_created', ($activityLogOptions->descriptionForEvent)('created'));
    }
}
