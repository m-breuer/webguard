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
                    'webhook_url' => 'https://hooks.slack.com/services/test',
                ],
                'discord' => [
                    'enabled' => true,
                    'webhook_url' => 'https://discord.com/api/webhooks/test',
                ],
            ],
        ]);

        Http::fake([
            'https://hooks.slack.com/*' => Http::response(['ok' => false], 500),
            'https://discord.com/*' => Http::response(['ok' => true], 204),
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

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['slack', 'discord']);

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

    public function test_router_skips_channels_not_selected_for_monitoring(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.com/services/test',
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

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, []);

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

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['slack']);

        $this->assertFalse($wasDelivered);
        Http::assertNothingSent();
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'slack',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SKIPPED->value,
        ]);
    }

    public function test_router_sends_to_teams_channel(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'teams' => [
                    'enabled' => true,
                    'webhook_url' => 'https://example.com/teams/webhook/123',
                ],
            ],
        ]);

        Http::fake([
            'https://example.com/*' => Http::response([], 200),
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

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['teams']);

        $this->assertTrue($wasDelivered);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/teams/webhook/123'
            && data_get($request->data(), 'type') === 'message'
            && data_get($request->data(), 'attachments.0.contentType') === 'application/vnd.microsoft.card.adaptive'
            && str_contains(json_encode($request->data(), JSON_THROW_ON_ERROR), 'Monitoring incident'));
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'teams',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
    }

    public function test_router_blocks_private_webhook_destinations_before_sending(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'webhook' => [
                    'enabled' => true,
                    'url' => 'http://127.0.0.1:8080/webhook',
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

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['webhook']);

        $this->assertFalse($wasDelivered);
        Http::assertNothingSent();
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::FAILED->value,
        ]);
    }
}
