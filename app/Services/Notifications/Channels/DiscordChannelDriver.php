<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use RuntimeException;
use Illuminate\Support\Facades\Http;

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
    public function send(NotificationPayload $payload, array $config): void
    {
        $webhookUrl = (string) ($config['webhook_url'] ?? '');
        $response = Http::timeout(10)->post($webhookUrl, [
            'content' => $payload->title . "\n" . $payload->message,
            'embeds' => [[
                'title' => $payload->title,
                'description' => $payload->message,
                'timestamp' => $payload->occurredAt->toIso8601String(),
                'fields' => [
                    ['name' => 'Event', 'value' => $payload->eventType->value, 'inline' => true],
                    ['name' => 'Severity', 'value' => $payload->severity, 'inline' => true],
                    ['name' => 'Monitoring', 'value' => $payload->monitoringName ?? 'n/a', 'inline' => false],
                    ['name' => 'Target', 'value' => $payload->monitoringTarget ?? 'n/a', 'inline' => false],
                ],
            ]],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Discord notification failed with status ' . $response->status());
        }
    }
}

