<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\NotificationChannelDelivery;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MobileNotificationBoardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_notification_board_is_cursor_paginated_scoped_and_read_safe(): void
    {
        Date::setTestNow('2026-08-09 20:00:00');
        $user = $this->user(['notification_channels' => ['mail' => ['enabled' => true]]]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $monitoringNotification = $this->notification($monitoring, NotificationType::STATUS_CHANGE, 'Service down', Date::now()->subMinutes(2));
        $second = $this->notification($monitoring, NotificationType::SSL_EXPIRY, 'SSL_EXPIRING', Date::now()->subMinute());
        NotificationChannelDelivery::query()->create([
            'user_id' => $user->id,
            'monitoring_notification_id' => $second->id,
            'channel' => 'slack',
            'event_type' => 'ssl_expiring',
            'status' => NotificationDeliveryStatus::FAILED,
            'error_message' => 'Webhook failed.',
        ]);
        $hidden = Monitoring::factory()->for($this->user())->create();
        $hiddenNotification = $this->notification($hidden, NotificationType::DOMAIN_EXPIRY, 'DOMAIN_EXPIRED', Date::now());
        $this->actingAsMobile($user);

        $testResponse = $this->getJson('/api/v1/mobile/notification-board?limit=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.0.event_type', 'ssl_expiring')
            ->assertJsonPath('data.0.severity', 'warning')
            ->assertJsonPath('data.0.delivery_status', 'failed')
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonMissing(['id' => $hiddenNotification->id]);

        $this->getJson('/api/v1/mobile/notification-board?limit=1&cursor=' . urlencode($testResponse->json('meta.next_cursor')))
            ->assertOk()
            ->assertJsonPath('data.0.id', $monitoringNotification->id);
        $this->getJson('/api/v1/mobile/notification-board?event_type=delivery_failure')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);
        $this->patchJson('/api/v1/mobile/notification-board/' . $second->id . '/read')
            ->assertOk()
            ->assertJsonPath('data.read', true);
        $this->patchJson('/api/v1/mobile/notification-board/' . $second->id . '/read')->assertOk();
        $this->patchJson('/api/v1/mobile/notification-board/read-all')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 0);
    }

    public function test_mobile_notification_preferences_follow_private_defaults(): void
    {
        $user = $this->user(['notification_channels' => ['mail' => ['enabled' => true], 'slack' => ['enabled' => true]]]);
        $monitoring = Monitoring::factory()->for($user)->create();
        $this->actingAsMobile($user);

        $this->getJson('/api/v1/mobile/monitorings/' . $monitoring->id . '/notification-preferences')
            ->assertOk()
            ->assertJsonPath('data.source', 'private_default')
            ->assertJsonPath('data.can_update', true);
        $this->patchJson('/api/v1/mobile/monitorings/' . $monitoring->id . '/notification-preferences', [
            'notification_on_failure' => false,
            'notification_channels' => ['slack'],
            'ssl_expiry_warning_days' => 14,
        ])->assertOk()
            ->assertJsonPath('data.effective.notification_channels.0', 'slack');
        $this->assertDatabaseHas('monitorings', ['id' => $monitoring->id, 'notification_on_failure' => false, 'ssl_expiry_warning_days' => 14]);
    }

    public function test_mobile_notification_preferences_are_per_team_member(): void
    {
        $user = $this->user();
        $member = $this->user(['notification_channels' => ['mail' => ['enabled' => true]]]);
        $team = Team::factory()->create(['created_by_user_id' => $user->id]);
        $team->memberships()->create(['user_id' => $user->id, 'role' => 'admin']);
        $team->memberships()->create(['user_id' => $member->id, 'role' => 'member']);
        $monitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'notification_on_failure' => true,
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 30,
        ]);
        $this->actingAsMobile($member);

        $this->patchJson('/api/v1/mobile/monitorings/' . $monitoring->id . '/notification-preferences', [
            'notification_on_failure' => false,
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 7,
        ])->assertOk()
            ->assertJsonPath('data.source', 'team_member');

        $this->assertDatabaseHas('monitoring_notification_preferences', [
            'monitoring_id' => $monitoring->id,
            'user_id' => $member->id,
            'notification_on_failure' => false,
            'ssl_expiry_warning_days' => 7,
        ]);
        $this->assertTrue($monitoring->refresh()->notification_on_failure);
        $this->assertSame(30, $monitoring->ssl_expiry_warning_days);
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create([...['package_id' => Package::factory()->create()->id], ...$attributes]);
    }

    private function notification(Monitoring $monitoring, NotificationType $notificationType, string $message, mixed $createdAt): MonitoringNotification
    {
        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => $notificationType,
            'message' => $message,
        ]);
        $monitoringNotification->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);

        return $monitoringNotification->refresh();
    }

    private function actingAsMobile(User $user): void
    {
        $this->withToken($user->createToken('ios-app: Test Device')->plainTextToken);
    }
}
