<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringIncidentPayload implements JsonSerializable
{
    public function __construct(
        public string $downAt,
        public ?string $upAt
    ) {}

    /**
     * @return array{down_at: string, up_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'down_at' => $this->downAt,
            'up_at' => $this->upAt,
        ];
    }

    /**
     * @return array{down_at: string, up_at: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
