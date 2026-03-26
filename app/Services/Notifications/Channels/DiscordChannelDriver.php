<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DiscordChannelDriver implements NotificationChannelDriver
{
    public function channel(): string
    {
        return NotificationChannel::DISCORD->value;
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
            'content' => $notificationPayload->title . "\n" . $notificationPayload->message,
            'embeds' => [[
                'title' => $notificationPayload->title,
                'description' => $notificationPayload->message,
                'timestamp' => $notificationPayload->occurredAt->toIso8601String(),
                'fields' => [
                    ['name' => 'Event', 'value' => $notificationPayload->eventType->value, 'inline' => true],
                    ['name' => 'Severity', 'value' => $notificationPayload->severity, 'inline' => true],
                    ['name' => 'Monitoring', 'value' => $notificationPayload->monitoringName ?? 'n/a', 'inline' => false],
                    ['name' => 'Target', 'value' => $notificationPayload->monitoringTarget ?? 'n/a', 'inline' => false],
                ],
            ]],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Discord notification failed with status ' . $response->status());
        }
    }
}
