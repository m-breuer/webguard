<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringNotificationBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_status_identifier_keys_relations_scopes_and_translated_messages(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Public API']);
        $downNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is DOWN',
            'read' => true,
            'sent' => true,
        ]);
        $unknownNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Status changed',
            'read' => false,
            'sent' => false,
        ]);
        $sslNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRED',
            'read' => false,
            'sent' => false,
        ]);
        $domainNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::DOMAIN_EXPIRY,
            'message' => 'DOMAIN_EXPIRING',
            'read' => false,
            'sent' => false,
        ]);
        $genericNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'Plain message',
            'read' => true,
            'sent' => false,
        ]);

        $this->assertSame('unknown', MonitoringNotification::extractStatusChangeIdentifierFromMessage('no signal'));
        $this->assertSame('maintenance', $downNotification->statusChangeIdentifier(true));
        $this->assertSame('notifications.status_change.down', $downNotification->statusChangeKey());
        $this->assertTrue($downNotification->monitoring->is($monitoring));
        $this->assertGreaterThan(0, $downNotification->states()->count());
        $this->assertSame(__('notifications.status_messages.down', ['name' => 'Public API']), $downNotification->translated_message);
        $this->assertSame('Status changed', $unknownNotification->translated_message);
        $this->assertSame(__('notifications.ssl_messages.expired', ['name' => 'Public API']), $sslNotification->translated_message);
        $this->assertSame(__('notifications.domain_messages.expiring', ['name' => 'Public API']), $domainNotification->translated_message);
        $this->assertSame('Plain message', $genericNotification->translated_message);
        $this->assertNotSame('', $downNotification->created_at_for_humans);
        $this->assertSame(2, MonitoringNotification::query()->withoutGlobalScopes()->read()->count());
        $this->assertSame(3, MonitoringNotification::query()->withoutGlobalScopes()->unread()->count());
        $this->assertSame(2, MonitoringNotification::query()->withoutGlobalScopes()->statusChange()->count());
        $this->assertSame(2, MonitoringNotification::query()->withoutGlobalScopes()->sslExpiry()->count());
        $this->assertSame(1, MonitoringNotification::query()->withoutGlobalScopes()->domainExpiry()->count());

        $this->actingAs($user);
        $this->assertSame(2, MonitoringNotification::query()->withoutGlobalScopes()->read()->count());
        $this->assertSame(3, MonitoringNotification::query()->withoutGlobalScopes()->unread()->count());
    }

    public function test_created_notification_state_is_created_for_each_team_member(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => 'admin']);
        $team->memberships()->create(['user_id' => $member->id, 'role' => 'member']);
        $monitoring = Monitoring::factory()->create(['user_id' => null, 'team_id' => $team->id]);

        $notification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is up',
            'read' => true,
            'sent' => true,
        ]);

        $this->assertDatabaseHas('monitoring_notification_states', [
            'monitoring_notification_id' => $notification->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('monitoring_notification_states', [
            'monitoring_notification_id' => $notification->id,
            'user_id' => $member->id,
        ]);
    }
}
