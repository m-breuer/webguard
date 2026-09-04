<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use App\Support\PubliclyRoutableUrl;
use RuntimeException;

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
    public function send(NotificationPayload $notificationPayload, array $config): void
    {
        $url = (string) ($config['url'] ?? '');

        $response = PubliclyRoutableUrl::post($url, $notificationPayload->toArray());

        if (! $response->successful()) {
            throw new RuntimeException('Webhook notification failed with status ' . $response->status());
        }
    }
}
