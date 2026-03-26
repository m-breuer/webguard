<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use RuntimeException;
use Illuminate\Support\Facades\Http;

class WebhookChannelDriver implements NotificationChannelDriver
{
    public function channel(): string
    {
        return NotificationChannel::WEBHOOK->value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        return filled($config['url'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function send(NotificationPayload $payload, array $config): void
    {
        $url = (string) ($config['url'] ?? '');
        $response = Http::timeout(10)->post($url, $payload->toArray());

        if (! $response->successful()) {
            throw new RuntimeException('Webhook notification failed with status ' . $response->status());
        }
    }
}

