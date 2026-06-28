<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\MonitoringNotificationPreference;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringNotificationPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_owner_preference_update_syncs_private_monitoring_defaults(): void
    {
        $user = User::factory()->create([
            'notification_channels' => [
                'mail' => ['enabled' => true],
                'slack' => ['enabled' => true],
            ],
        ]);
        $monitoring = Monitoring::factory()->for($user)->create([
            'notification_on_failure' => true,
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 30,
        ]);

        $this->actingAs($user)->patch(route('monitorings.notification-preferences.update', $monitoring), [
            'notification_on_failure' => '0',
            'notification_channels' => ['slack', 'slack'],
            'ssl_expiry_warning_days' => 14,
        ])->assertRedirect();

        $this->assertDatabaseHas('monitoring_notification_preferences', [
            'monitoring_id' => $monitoring->id,
            'user_id' => $user->id,
            'notification_on_failure' => false,
            'ssl_expiry_warning_days' => 14,
        ]);
        $this->assertSame(['slack'], MonitoringNotificationPreference::query()->firstOrFail()->notification_channels);
        $monitoring->refresh();
        $this->assertFalse($monitoring->notification_on_failure);
        $this->assertSame(['slack'], $monitoring->notification_channels);
        $this->assertSame(14, $monitoring->ssl_expiry_warning_days);
    }

    public function test_team_member_preference_does_not_sync_monitoring_defaults(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create([
            'notification_channels' => ['mail' => ['enabled' => true]],
        ]);
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        $team->memberships()->create(['user_id' => $owner->id, 'role' => 'admin']);
        $team->memberships()->create(['user_id' => $member->id, 'role' => 'member']);
        $monitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'notification_on_failure' => true,
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 30,
        ]);

        $this->actingAs($member)->patch(route('monitorings.notification-preferences.update', $monitoring), [
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 7,
        ])->assertRedirect();

        $this->assertDatabaseHas('monitoring_notification_preferences', [
            'monitoring_id' => $monitoring->id,
            'user_id' => $member->id,
            'notification_on_failure' => false,
            'ssl_expiry_warning_days' => 7,
        ]);
        $this->assertTrue($monitoring->refresh()->notification_on_failure);
        $this->assertSame(30, $monitoring->ssl_expiry_warning_days);
    }

    public function test_preference_update_blocks_demo_and_invisible_monitorings(): void
    {
        $demoUser = User::factory()->create(['role' => UserRole::DEMO]);
        $demoMonitoring = Monitoring::factory()->for($demoUser)->create();

        $this->actingAs($demoUser)->patch(route('monitorings.notification-preferences.update', $demoMonitoring), [
            'ssl_expiry_warning_days' => 7,
        ])->assertForbidden();

        $owner = User::factory()->create();
        $monitoring = Monitoring::factory()->for($owner)->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->patch(route('monitorings.notification-preferences.update', $monitoring), [
            'ssl_expiry_warning_days' => 7,
        ])->assertNotFound();
    }
}
