<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\NotificationEventType;
use Carbon\CarbonInterface;

class NotificationPayload
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly NotificationEventType $eventType,
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity,
        public readonly ?string $monitoringId,
        public readonly ?string $monitoringName,
        public readonly ?string $monitoringTarget,
        public readonly CarbonInterface $occurredAt,
        public readonly array $meta = []
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType->value,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'monitoring' => [
                'id' => $this->monitoringId,
                'name' => $this->monitoringName,
                'target' => $this->monitoringTarget,
            ],
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'meta' => $this->meta,
        ];
    }
}
