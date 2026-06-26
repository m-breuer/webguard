<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApnsClient
{
    public function isConfigured(): bool
    {
        return filled($this->keyId())
            && filled($this->teamId())
            && filled($this->topic())
            && filled($this->privateKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function sendToToken(string $token, NotificationPayload $notificationPayload): array
    {
        throw_unless($this->isConfigured(), RuntimeException::class, 'APNs credentials are not configured.');

        $response = Http::timeout(10)
            ->withToken($this->jwt())
            ->withHeaders([
                'apns-topic' => $this->topic(),
                'apns-push-type' => 'alert',
                'apns-priority' => '10',
            ])
            ->withOptions(['version' => 2.0])
            ->post($this->endpoint() . '/3/device/' . $token, $this->payload($notificationPayload));

        if (! $response->successful()) {
            $reason = (string) $response->json('reason', $response->body());

            throw new RuntimeException('APNs notification failed with status ' . $response->status() . ': ' . $reason);
        }

        return $response->json() ?? [];
    }

    private function keyId(): string
    {
        return (string) config('services.apns.key_id', '');
    }

    private function teamId(): string
    {
        return (string) config('services.apns.team_id', '');
    }

    private function topic(): string
    {
        return (string) config('services.apns.bundle_id', '');
    }

    private function endpoint(): string
    {
        return (string) config('services.apns.environment') === 'development'
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';
    }

    private function privateKey(): string
    {
        $privateKey = (string) config('services.apns.private_key', '');

        if ($privateKey !== '') {
            return str_replace('\n', "\n", $privateKey);
        }

        $path = (string) config('services.apns.private_key_path', '');

        if ($path === '' || ! is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }

    private function jwt(): string
    {
        $issuedAt = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'ES256', 'kid' => $this->keyId()], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode(['iss' => $this->teamId(), 'iat' => $issuedAt], JSON_THROW_ON_ERROR));
        $signingInput = $header . '.' . $claims;
        $signature = '';

        $signed = openssl_sign($signingInput, $signature, $this->privateKey(), OPENSSL_ALGO_SHA256);

        throw_unless($signed, RuntimeException::class, 'Unable to sign APNs provider token.');

        return $signingInput . '.' . $this->base64UrlEncode($this->derSignatureToRaw($signature));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(NotificationPayload $notificationPayload): array
    {
        return [
            'aps' => [
                'alert' => [
                    'title' => $notificationPayload->title,
                    'body' => $notificationPayload->message,
                ],
                'sound' => 'default',
                'category' => 'MONITORING_ALERT',
            ],
            'event_type' => $notificationPayload->eventType->value,
            'severity' => $notificationPayload->severity,
            'monitoring_id' => (string) $notificationPayload->monitoringId,
            'monitoring_name' => (string) $notificationPayload->monitoringName,
            'monitoring_target' => (string) $notificationPayload->monitoringTarget,
            'occurred_at' => $notificationPayload->occurredAt->toIso8601String(),
            'notification_id' => (string) data_get($notificationPayload->meta, 'notification_id', ''),
        ];
    }

    private function derSignatureToRaw(string $signature): string
    {
        $offset = 0;

        throw_unless(ord($signature[$offset++] ?? "\0") === 0x30, RuntimeException::class, 'Invalid APNs signature sequence.');
        $this->readDerLength($signature, $offset);

        throw_unless(ord($signature[$offset++] ?? "\0") === 0x02, RuntimeException::class, 'Invalid APNs signature integer.');
        $rLength = $this->readDerLength($signature, $offset);
        $r = mb_substr($signature, $offset, $rLength);
        $offset += $rLength;

        throw_unless(ord($signature[$offset++] ?? "\0") === 0x02, RuntimeException::class, 'Invalid APNs signature integer.');
        $sLength = $this->readDerLength($signature, $offset);
        $s = mb_substr($signature, $offset, $sLength);

        return mb_str_pad(mb_ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT)
            . mb_str_pad(mb_ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }

    private function readDerLength(string $value, int &$offset): int
    {
        $length = ord($value[$offset++] ?? "\0");

        if ($length < 0x80) {
            return $length;
        }

        $bytes = $length & 0x7F;
        $length = 0;

        for ($index = 0; $index < $bytes; $index++) {
            $length = ($length << 8) + ord($value[$offset++] ?? "\0");
        }

        return $length;
    }

    private function base64UrlEncode(string $value): string
    {
        return mb_rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
