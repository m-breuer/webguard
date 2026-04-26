<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringSslResult;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendSslExpiryWarningsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_ssl_expiring_notifications_to_enabled_channels(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.test/services/test',
                ],
                'webhook' => [
                    'enabled' => true,
                    'url' => 'https://example.test/webhook',
                ],
            ],
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'notification_on_failure' => true,
            'notification_channels' => ['slack', 'webhook'],
            'ssl_expiry_warning_days' => 7,
        ]);

        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'expires_at' => now()->addDays(3),
            'is_valid' => true,
            'issuer' => 'LetsEncrypt',
            'issued_at' => now()->subDays(60),
        ]);

        Http::fake([
            'https://hooks.slack.test/*' => Http::response(['ok' => true], 200),
            'https://example.test/*' => Http::response(['ok' => true], 200),
        ]);

        Artisan::call('notifications:send-ssl-expiry-warnings');

        Http::assertSentCount(2);
        $this->assertDatabaseHas('monitoring_notifications', [
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY->value,
            'message' => 'SSL_EXPIRING',
            'sent' => true,
        ]);

        $monitoringNotification = MonitoringNotification::query()
            ->where('monitoring_id', $monitoring->id)
            ->where('type', NotificationType::SSL_EXPIRY->value)
            ->firstOrFail();

        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'monitoring_notification_id' => $monitoringNotification->id,
            'channel' => 'slack',
            'event_type' => NotificationEventType::SSL_EXPIRING->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'monitoring_notification_id' => $monitoringNotification->id,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::SSL_EXPIRING->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
    }

    public function test_ssl_command_respects_per_monitoring_notification_flag(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'webhook' => [
                    'enabled' => true,
                    'url' => 'https://example.test/webhook',
                ],
            ],
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'notification_on_failure' => false,
            'notification_channels' => ['webhook'],
            'ssl_expiry_warning_days' => 7,
        ]);

        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'expires_at' => now()->addDays(2),
            'is_valid' => true,
            'issuer' => 'LetsEncrypt',
            'issued_at' => now()->subDays(60),
        ]);

        Http::fake();

        Artisan::call('notifications:send-ssl-expiry-warnings');

        Http::assertNothingSent();
        $this->assertSame(
            0,
            MonitoringNotification::query()
                ->where('monitoring_id', $monitoring->id)
                ->where('type', NotificationType::SSL_EXPIRY->value)
                ->count()
        );
        $this->assertDatabaseCount('notification_channel_deliveries', 0);
    }

    public function test_ssl_command_respects_per_monitoring_warning_window(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'webhook' => [
                    'enabled' => true,
                    'url' => 'https://example.test/webhook',
                ],
            ],
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'notification_on_failure' => true,
            'notification_channels' => ['webhook'],
            'ssl_expiry_warning_days' => 3,
        ]);

        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'expires_at' => now()->addDays(10),
            'is_valid' => true,
            'issuer' => 'LetsEncrypt',
            'issued_at' => now()->subDays(60),
        ]);

        Http::fake();

        Artisan::call('notifications:send-ssl-expiry-warnings');

        Http::assertNothingSent();
        $this->assertDatabaseMissing('monitoring_notifications', [
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY->value,
        ]);
    }
}
