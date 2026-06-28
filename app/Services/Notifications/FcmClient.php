<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmClient
{
    public function isConfigured(): bool
    {
        return filled($this->projectId())
            && (filled(config('services.fcm.access_token'))
                || filled(config('services.fcm.service_account_json'))
                || filled(config('services.fcm.service_account_path')));
    }

    /**
     * @return array<string, mixed>
     */
    public function sendToToken(string $token, NotificationPayload $notificationPayload): array
    {
        $projectId = $this->projectId();

        throw_if(blank($projectId), RuntimeException::class, 'FCM project id is not configured.');

        $response = Http::timeout(10)
            ->withToken($this->accessToken())
            ->post(sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId), [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notificationPayload->title,
                        'body' => $notificationPayload->message,
                    ],
                    'data' => $this->dataPayload($notificationPayload),
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'channel_id' => 'monitoring_alerts',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'category' => 'MONITORING_ALERT',
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('FCM notification failed with status ' . $response->status() . ': ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function projectId(): string
    {
        return (string) config('services.fcm.project_id', '');
    }

    private function accessToken(): string
    {
        $configuredAccessToken = (string) config('services.fcm.access_token', '');

        if (filled($configuredAccessToken)) {
            return $configuredAccessToken;
        }

        $serviceAccount = $this->serviceAccount();
        $cacheKey = 'fcm_access_token:' . hash('sha256', (string) ($serviceAccount['client_email'] ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($serviceAccount): string {
            $jwt = $this->serviceAccountJwt($serviceAccount);
            $tokenUri = (string) ($serviceAccount['token_uri'] ?? config('services.fcm.token_uri'));

            $response = Http::asForm()->timeout(10)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('FCM OAuth token request failed with status ' . $response->status() . ': ' . $response->body());
            }

            $accessToken = $response->json('access_token');

            throw_unless(is_string($accessToken) && $accessToken !== '', RuntimeException::class, 'FCM OAuth token response did not include an access token.');

            return $accessToken;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceAccount(): array
    {
        $serviceAccountJson = (string) config('services.fcm.service_account_json', '');

        if ($serviceAccountJson === '') {
            $path = (string) config('services.fcm.service_account_path', '');

            throw_if($path === '' || ! is_readable($path), RuntimeException::class, 'FCM service account credentials are not configured.');

            $serviceAccountJson = (string) file_get_contents($path);
        }

        $serviceAccount = json_decode($serviceAccountJson, true);

        throw_unless(is_array($serviceAccount), RuntimeException::class, 'FCM service account credentials are invalid JSON.');

        foreach (['client_email', 'private_key'] as $requiredKey) {
            throw_unless(is_string($serviceAccount[$requiredKey] ?? null) && $serviceAccount[$requiredKey] !== '', RuntimeException::class, 'FCM service account credentials are incomplete.');
        }

        return $serviceAccount;
    }

    /**
     * @param  array<string, mixed>  $serviceAccount
     */
    private function serviceAccountJwt(array $serviceAccount): string
    {
        $issuedAt = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccount['token_uri'] ?? config('services.fcm.token_uri'),
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_THROW_ON_ERROR));
        $signingInput = $header . '.' . $claims;
        $signature = '';

        $signed = openssl_sign($signingInput, $signature, (string) $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);

        throw_unless($signed, RuntimeException::class, 'Unable to sign FCM service account assertion.');

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return mb_rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @return array<string, string>
     */
    private function dataPayload(NotificationPayload $notificationPayload): array
    {
        return [
            'event_type' => $notificationPayload->eventType->value,
            'severity' => $notificationPayload->severity,
            'monitoring_id' => (string) $notificationPayload->monitoringId,
            'monitoring_name' => (string) $notificationPayload->monitoringName,
            'monitoring_target' => (string) $notificationPayload->monitoringTarget,
            'occurred_at' => $notificationPayload->occurredAt->toIso8601String(),
            'notification_id' => (string) data_get($notificationPayload->meta, 'notification_id', ''),
        ];
    }
}
