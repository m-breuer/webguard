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

    public function dispatch(User $user, NotificationPayload $notificationPayload): bool
    {
        $channels = $this->resolveChannelsForEvent($user, $notificationPayload->eventType);
        $monitoringNotificationId = $this->resolveMonitoringNotificationId($notificationPayload);

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
                    'event_type' => $notificationPayload->eventType->value,
                ]);
                $this->logDelivery(
                    $user,
                    $notificationPayload,
                    $channel,
                    NotificationDeliveryStatus::SKIPPED,
                    $monitoringNotificationId,
                    'Channel is enabled but missing required credentials.'
                );

                continue;
            }

            try {
                $driver->send($notificationPayload, $config);
                $this->logDelivery(
                    $user,
                    $notificationPayload,
                    $channel,
                    NotificationDeliveryStatus::SENT,
                    $monitoringNotificationId,
                    sentAt: now()
                );
                $hasSuccess = true;
            } catch (Throwable $throwable) {
                Log::error('Notification delivery failed.', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                    'event_type' => $notificationPayload->eventType->value,
                    'exception' => $throwable->getMessage(),
                ]);
                $this->logDelivery(
                    $user,
                    $notificationPayload,
                    $channel,
                    NotificationDeliveryStatus::FAILED,
                    $monitoringNotificationId,
                    $throwable->getMessage()
                );
            }
        }

        return $hasSuccess;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveChannelsForEvent(User $user, NotificationEventType $notificationEventType): array
    {
        $configuredChannels = is_array($user->notification_channels) ? $user->notification_channels : [];
        $activeChannels = [];

        foreach ($this->drivers as $channel => $driver) {
            $channelConfig = $configuredChannels[$channel] ?? null;

            if (! is_array($channelConfig)) {
                continue;
            }

            $enabled = (bool) ($channelConfig['enabled'] ?? false);
            $eventEnabled = (bool) data_get($channelConfig, 'events.' . $notificationEventType->value, false);

            if ($enabled && $eventEnabled) {
                $activeChannels[$channel] = $channelConfig;
            }
        }

        return $activeChannels;
    }

    private function resolveMonitoringNotificationId(NotificationPayload $notificationPayload): ?string
    {
        $notificationId = data_get($notificationPayload->meta, 'notification_id');

        if (! is_string($notificationId) || $notificationId === '') {
            return null;
        }

        return $notificationId;
    }

    private function logDelivery(
        User $user,
        NotificationPayload $notificationPayload,
        string $channel,
        NotificationDeliveryStatus $notificationDeliveryStatus,
        ?string $monitoringNotificationId = null,
        ?string $errorMessage = null,
        ?CarbonInterface $sentAt = null
    ): void {
        NotificationChannelDelivery::query()->create([
            'user_id' => $user->id,
            'monitoring_notification_id' => $monitoringNotificationId,
            'channel' => $channel,
            'event_type' => $notificationPayload->eventType->value,
            'status' => $notificationDeliveryStatus,
            'payload' => $notificationPayload->toArray(),
            'error_message' => $errorMessage,
            'sent_at' => $sentAt,
        ]);
    }
}
