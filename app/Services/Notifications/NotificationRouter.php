<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Models\NotificationChannelDelivery;
use App\Models\User;
use App\Services\Notifications\Channels\DiscordChannelDriver;
use App\Services\Notifications\Channels\NotificationChannelDriver;
use App\Services\Notifications\Channels\SlackChannelDriver;
use App\Services\Notifications\Channels\TelegramChannelDriver;
use App\Services\Notifications\Channels\WebhookChannelDriver;
use Carbon\CarbonInterface;
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
        $monitoringNotificationId = $this->resolveMonitoringNotificationId($payload);

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
                $this->logDelivery(
                    user: $user,
                    payload: $payload,
                    channel: $channel,
                    status: NotificationDeliveryStatus::SKIPPED,
                    monitoringNotificationId: $monitoringNotificationId,
                    errorMessage: 'Channel is enabled but missing required credentials.'
                );

                continue;
            }

            try {
                $driver->send($payload, $config);
                $this->logDelivery(
                    user: $user,
                    payload: $payload,
                    channel: $channel,
                    status: NotificationDeliveryStatus::SENT,
                    monitoringNotificationId: $monitoringNotificationId,
                    sentAt: now()
                );
                $hasSuccess = true;
            } catch (Throwable $throwable) {
                Log::error('Notification delivery failed.', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                    'event_type' => $payload->eventType->value,
                    'exception' => $throwable->getMessage(),
                ]);
                $this->logDelivery(
                    user: $user,
                    payload: $payload,
                    channel: $channel,
                    status: NotificationDeliveryStatus::FAILED,
                    monitoringNotificationId: $monitoringNotificationId,
                    errorMessage: $throwable->getMessage()
                );
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

    private function resolveMonitoringNotificationId(NotificationPayload $payload): ?string
    {
        $notificationId = data_get($payload->meta, 'notification_id');

        if (! is_string($notificationId) || $notificationId === '') {
            return null;
        }

        return $notificationId;
    }

    private function logDelivery(
        User $user,
        NotificationPayload $payload,
        string $channel,
        NotificationDeliveryStatus $status,
        ?string $monitoringNotificationId = null,
        ?string $errorMessage = null,
        ?CarbonInterface $sentAt = null
    ): void {
        NotificationChannelDelivery::query()->create([
            'user_id' => $user->id,
            'monitoring_notification_id' => $monitoringNotificationId,
            'channel' => $channel,
            'event_type' => $payload->eventType->value,
            'status' => $status,
            'payload' => $payload->toArray(),
            'error_message' => $errorMessage,
            'sent_at' => $sentAt,
        ]);
    }
}
