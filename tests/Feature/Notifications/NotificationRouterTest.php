<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Models\Package;
use App\Models\User;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_sends_to_multiple_channels_and_continues_on_single_channel_failure(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.test/services/test',
                    'events' => ['incident' => true],
                ],
                'discord' => [
                    'enabled' => true,
                    'webhook_url' => 'https://discord.test/api/webhooks/test',
                    'events' => ['incident' => true],
                ],
            ],
        ]);

        Http::fake([
            'https://hooks.slack.test/*' => Http::response(['ok' => false], 500),
            'https://discord.test/*' => Http::response(['ok' => true], 204),
        ]);

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now(),
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload);

        $this->assertTrue($wasDelivered);
        Http::assertSentCount(2);
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'slack',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::FAILED->value,
        ]);
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'discord',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
    }

    public function test_router_skips_channels_without_enabled_event(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.test/services/test',
                    'events' => ['incident' => false, 'recovery' => true],
                ],
            ],
        ]);

        Http::fake();

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now(),
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload);

        $this->assertFalse($wasDelivered);
        Http::assertNothingSent();
        $this->assertDatabaseCount('notification_channel_deliveries', 0);
    }

    public function test_router_logs_skipped_delivery_for_misconfigured_channel(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'events' => ['incident' => true],
                ],
            ],
        ]);

        Http::fake();

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now(),
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload);

        $this->assertFalse($wasDelivered);
        Http::assertNothingSent();
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'slack',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SKIPPED->value,
        ]);
    }
}
