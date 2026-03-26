<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\NotificationEventType;
use App\Models\User;
use App\Services\Notifications\Channels\DiscordChannelDriver;
use App\Services\Notifications\Channels\NotificationChannelDriver;
use App\Services\Notifications\Channels\SlackChannelDriver;
use App\Services\Notifications\Channels\TelegramChannelDriver;
use App\Services\Notifications\Channels\WebhookChannelDriver;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationRouter
{
    /**
     * @var array<string, NotificationChannelDriver>
     */
    private array $drivers;

    public function __construct(
        SlackChannelDriver $slackChannelDriver,
        TelegramChannelDriver $telegramChannelDriver,
        DiscordChannelDriver $discordChannelDriver,
        WebhookChannelDriver $webhookChannelDriver
    ) {
        $this->drivers = [
            $slackChannelDriver->channel() => $slackChannelDriver,
            $telegramChannelDriver->channel() => $telegramChannelDriver,
            $discordChannelDriver->channel() => $discordChannelDriver,
            $webhookChannelDriver->channel() => $webhookChannelDriver,
        ];
    }

    public function dispatch(User $user, NotificationPayload $payload): bool
    {
        $channels = $this->resolveChannelsForEvent($user, $payload->eventType);

        $hasSuccess = false;

        foreach ($channels as $channel => $config) {
            $driver = $this->drivers[$channel] ?? null;

            if (! $driver) {
                continue;
            }

            if (! $driver->isConfigured($config)) {
                Log::warning('Skipping misconfigured notification channel.', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                    'event_type' => $payload->eventType->value,
                ]);

                continue;
            }

            try {
                $driver->send($payload, $config);
                $hasSuccess = true;
            } catch (Throwable $throwable) {
                Log::error('Notification delivery failed.', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                    'event_type' => $payload->eventType->value,
                    'exception' => $throwable->getMessage(),
                ]);
            }
        }

        return $hasSuccess;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveChannelsForEvent(User $user, NotificationEventType $eventType): array
    {
        $configuredChannels = is_array($user->notification_channels) ? $user->notification_channels : [];
        $activeChannels = [];

        foreach ($this->drivers as $channel => $driver) {
            $channelConfig = $configuredChannels[$channel] ?? null;

            if (! is_array($channelConfig)) {
                continue;
            }

            $enabled = (bool) ($channelConfig['enabled'] ?? false);
            $eventEnabled = (bool) data_get($channelConfig, 'events.' . $eventType->value, false);

            if ($enabled && $eventEnabled) {
                $activeChannels[$channel] = $channelConfig;
            }
        }

        return $activeChannels;
    }
}

