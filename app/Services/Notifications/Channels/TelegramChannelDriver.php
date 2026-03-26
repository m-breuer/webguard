<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use RuntimeException;
use Illuminate\Support\Facades\Http;

class TelegramChannelDriver implements NotificationChannelDriver
{
    public function channel(): string
    {
        return NotificationChannel::TELEGRAM->value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        return filled($config['bot_token'] ?? null) && filled($config['chat_id'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function send(NotificationPayload $payload, array $config): void
    {
        $botToken = (string) ($config['bot_token'] ?? '');
        $chatId = (string) ($config['chat_id'] ?? '');
        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);

        $response = Http::timeout(10)->post($endpoint, [
            'chat_id' => $chatId,
            'text' => implode("\n", [
                $payload->title,
                $payload->message,
                'Severity: ' . $payload->severity,
                'Event: ' . $payload->eventType->value,
                'Monitoring: ' . ($payload->monitoringName ?? 'n/a'),
                'Target: ' . ($payload->monitoringTarget ?? 'n/a'),
            ]),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram notification failed with status ' . $response->status());
        }
    }
}

