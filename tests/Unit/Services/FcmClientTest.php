<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\NotificationEventType;
use App\Services\Notifications\FcmClient;
use App\Services\Notifications\NotificationPayload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_service_account_json_is_exchanged_for_access_token_before_sending_notification(): void
    {
        config([
            'services.fcm.project_id' => 'webguard-test',
            'services.fcm.access_token' => '',
            'services.fcm.service_account_json' => json_encode([
                'client_email' => 'firebase-admin@example.test',
                'private_key' => $this->rsaPrivateKey(),
                'token_uri' => 'https://oauth.example.test/token',
            ], JSON_THROW_ON_ERROR),
            'services.fcm.service_account_path' => '',
            'services.fcm.token_uri' => 'https://oauth.example.test/token',
        ]);

        Http::fake([
            'https://oauth.example.test/token' => Http::response(['access_token' => 'oauth-access-token'], 200),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/webguard-test/messages/789'], 200),
        ]);

        $response = (new FcmClient())->sendToToken('fcm-device-token', new NotificationPayload(
            eventType: NotificationEventType::INCIDENT,
            title: 'Monitoring incident',
            message: 'Service is down.',
            severity: 'critical',
            monitoringId: '01TEST',
            monitoringName: 'API',
            monitoringTarget: 'https://example.test',
            occurredAt: Date::parse('2026-06-27 08:00:00', 'UTC'),
            meta: ['notification_id' => '01NOTIFICATION'],
        ));

        $this->assertSame(['name' => 'projects/webguard-test/messages/789'], $response);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://oauth.example.test/token'
            && data_get($request->data(), 'grant_type') === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && is_string(data_get($request->data(), 'assertion'))
            && mb_substr_count(data_get($request->data(), 'assertion'), '.') === 2);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://fcm.googleapis.com/v1/projects/webguard-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer oauth-access-token')
            && data_get($request->data(), 'message.token') === 'fcm-device-token'
            && data_get($request->data(), 'message.notification.title') === 'Monitoring incident'
            && data_get($request->data(), 'message.data.notification_id') === '01NOTIFICATION'
            && data_get($request->data(), 'message.android.notification.channel_id') === 'monitoring_alerts'
            && data_get($request->data(), 'message.apns.payload.aps.category') === 'MONITORING_ALERT');
    }

    private function rsaPrivateKey(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $privateKey = '';
        openssl_pkey_export($key, $privateKey);

        return $privateKey;
    }
}
