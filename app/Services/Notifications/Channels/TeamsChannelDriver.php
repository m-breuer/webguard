<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Services\Notifications\NotificationPayload;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TeamsChannelDriver implements NotificationChannelDriver
{
    public function channel(): string
    {
        return NotificationChannel::TEAMS->value;
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
        $response = Http::timeout(10)->post($webhookUrl, $this->payload($notificationPayload));

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Teams notification failed with status ' . $response->status());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(NotificationPayload $notificationPayload): array
    {
        $facts = [
            [
                'title' => 'Event',
                'value' => $notificationPayload->eventType->value,
            ],
            [
                'title' => 'Severity',
                'value' => $notificationPayload->severity,
            ],
        ];

        if ($notificationPayload->monitoringName !== null) {
            $facts[] = [
                'title' => 'Monitoring',
                'value' => $notificationPayload->monitoringName,
            ];
        }

        if ($notificationPayload->monitoringTarget !== null) {
            $facts[] = [
                'title' => 'Target',
                'value' => $notificationPayload->monitoringTarget,
            ];
        }

        return [
            'type' => 'message',
            'attachments' => [[
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'contentUrl' => null,
                'content' => [
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'type' => 'AdaptiveCard',
                    'version' => '1.0',
                    'body' => [
                        [
                            'type' => 'TextBlock',
                            'text' => $notificationPayload->title,
                            'weight' => 'Bolder',
                            'size' => 'Medium',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => $notificationPayload->message,
                            'wrap' => true,
                        ],
                        [
                            'type' => 'FactSet',
                            'facts' => $facts,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => $notificationPayload->occurredAt->toIso8601String(),
                            'isSubtle' => true,
                            'size' => 'Small',
                            'wrap' => true,
                        ],
                    ],
                ],
            ]],
        ];
    }
}
