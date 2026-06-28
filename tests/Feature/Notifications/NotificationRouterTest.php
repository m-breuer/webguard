<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Models\MobilePushDevice;
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

    public function test_router_sends_to_mobile_push_devices(): void
    {
        config([
            'services.fcm.project_id' => 'webguard-test',
            'services.fcm.access_token' => 'test-access-token',
        ]);

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'mobile_push' => [
                    'enabled' => true,
                ],
            ],
        ]);
        MobilePushDevice::factory()->for($user)->create([
            'platform' => 'ios',
            'push_token' => 'fcm-device-token',
            'token_hash' => hash('sha256', 'fcm-device-token'),
        ]);

        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/webguard-test/messages/123'], 200),
        ]);

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now()
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['mobile_push']);

        $this->assertTrue($wasDelivered);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://fcm.googleapis.com/v1/projects/webguard-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && data_get($request->data(), 'message.token') === 'fcm-device-token'
            && data_get($request->data(), 'message.notification.title') === 'Monitoring incident'
            && data_get($request->data(), 'message.data.notification_id') === '');
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'mobile_push',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
    }

    public function test_router_revokes_unregistered_mobile_push_device_when_another_device_succeeds(): void
    {
        config([
            'services.fcm.project_id' => 'webguard-test',
            'services.fcm.access_token' => 'test-access-token',
        ]);

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'mobile_push' => [
                    'enabled' => true,
                ],
            ],
        ]);
        $staleDevice = MobilePushDevice::factory()->for($user)->create([
            'push_token' => 'stale-fcm-token',
            'token_hash' => hash('sha256', 'stale-fcm-token'),
        ]);
        $activeDevice = MobilePushDevice::factory()->for($user)->create([
            'push_token' => 'active-fcm-token',
            'token_hash' => hash('sha256', 'active-fcm-token'),
        ]);

        Http::fake(function ($request) {
            if (data_get($request->data(), 'message.token') === 'stale-fcm-token') {
                return Http::response(['error' => ['status' => 'UNREGISTERED']], 404);
            }

            return Http::response(['name' => 'projects/webguard-test/messages/456'], 200);
        });

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now()
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['mobile_push']);

        $this->assertTrue($wasDelivered);
        Http::assertSentCount(2);
        $this->assertDatabaseHas('mobile_push_devices', [
            'id' => $staleDevice->id,
            'enabled' => false,
        ]);
        $this->assertNotNull($staleDevice->fresh()->revoked_at);
        $this->assertDatabaseHas('mobile_push_devices', [
            'id' => $activeDevice->id,
            'enabled' => true,
            'revoked_at' => null,
        ]);
        $this->assertNotNull($activeDevice->fresh()->last_seen_at);
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'mobile_push',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
    }

    public function test_router_records_failed_mobile_push_delivery_when_every_device_is_rejected(): void
    {
        config([
            'services.fcm.project_id' => 'webguard-test',
            'services.fcm.access_token' => 'test-access-token',
        ]);

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'mobile_push' => [
                    'enabled' => true,
                ],
            ],
        ]);
        $staleDevice = MobilePushDevice::factory()->for($user)->create([
            'push_token' => 'gone-fcm-token',
            'token_hash' => hash('sha256', 'gone-fcm-token'),
        ]);

        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['error' => ['message' => 'Requested entity was not found']], 404),
        ]);

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now()
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['mobile_push']);

        $this->assertFalse($wasDelivered);
        $this->assertDatabaseHas('mobile_push_devices', [
            'id' => $staleDevice->id,
            'enabled' => false,
        ]);
        $this->assertNotNull($staleDevice->fresh()->revoked_at);
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'mobile_push',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::FAILED->value,
        ]);
    }

    public function test_router_sends_to_apns_mobile_push_devices(): void
    {
        $privateKey = $this->test_ec_private_key();

        config([
            'services.apns.key_id' => 'ABC123DEFG',
            'services.apns.team_id' => 'TEAM123456',
            'services.apns.bundle_id' => 'dev.marcelbreuer.webguard',
            'services.apns.private_key' => $privateKey,
            'services.apns.environment' => 'development',
        ]);

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'mobile_push' => [
                    'enabled' => true,
                ],
            ],
        ]);
        MobilePushDevice::factory()->for($user)->create([
            'platform' => 'ios',
            'push_provider' => 'apns',
            'push_token' => 'apns-device-token',
            'token_hash' => hash('sha256', 'apns-device-token'),
        ]);

        Http::fake([
            'https://api.sandbox.push.apple.com/*' => Http::response(null, 200),
        ]);

        $notificationPayload = new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: now()
        );

        $wasDelivered = resolve(NotificationRouter::class)->dispatch($user, $notificationPayload, ['mobile_push']);

        $this->assertTrue($wasDelivered);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.sandbox.push.apple.com/3/device/apns-device-token'
            && str_starts_with((string) data_get($request->header('Authorization'), 0), 'Bearer ')
            && $request->hasHeader('apns-topic', 'dev.marcelbreuer.webguard')
            && $request->hasHeader('apns-push-type', 'alert')
            && data_get($request->data(), 'aps.alert.title') === 'Monitoring incident'
            && data_get($request->data(), 'monitoring_id') === '01TEST');
        $this->assertDatabaseHas('notification_channel_deliveries', [
            'user_id' => $user->id,
            'channel' => 'mobile_push',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);
    }

    private function test_ec_private_key(): string
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        $privateKey = '';
        openssl_pkey_export($key, $privateKey);

        return $privateKey;
    }
}
