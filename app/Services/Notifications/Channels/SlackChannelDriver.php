<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SlackChannelDriver implements NotificationChannelDriver
{
    public function channel(): string
    {
        return NotificationChannel::SLACK->value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        return filled($config['webhook_url'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function send(NotificationPayload $notificationPayload, array $config): void
    {
        $webhookUrl = (string) ($config['webhook_url'] ?? '');
        $response = Http::timeout(10)->post($webhookUrl, [
            'text' => $notificationPayload->title . "\n" . $notificationPayload->message,
            'payload' => $notificationPayload->toArray(),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Slack notification failed with status ' . $response->status());
        }
    }
}
