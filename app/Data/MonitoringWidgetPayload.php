<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringWidgetPayload implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $status,
        public string $statusLabel,
        public ?int $statusCode,
        public string $statusIdentifier,
        public string $statusKey,
        public ?string $checkedAt,
        public ?string $checkedAtHuman,
        public MonitoringWidgetUptimePayload $uptime,
        public string $publicUrl
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'status_code' => $this->statusCode,
            'status_identifier' => $this->statusIdentifier,
            'status_key' => $this->statusKey,
            'checked_at' => $this->checkedAt,
            'checked_at_human' => $this->checkedAtHuman,
            'uptime' => $this->uptime->toArray(),
            'public_url' => $this->publicUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
