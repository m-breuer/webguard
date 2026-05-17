<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringAvailabilitySegmentPayload implements JsonSerializable
{
    public function __construct(
        public int $minutes,
        public ?float $percentage,
        public int $total,
        public ?int $incidentsCount = null
    ) {}

    /**
     * @return array{minutes: int, percentage: float|null, total: int, incidents_count?: int}
     */
    public function toArray(): array
    {
        $payload = [
            'minutes' => $this->minutes,
            'percentage' => $this->percentage,
            'total' => $this->total,
        ];

        if ($this->incidentsCount !== null) {
            $payload['incidents_count'] = $this->incidentsCount;
        }

        return $payload;
    }

    /**
     * @return array{minutes: int, percentage: float|null, total: int, incidents_count?: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
