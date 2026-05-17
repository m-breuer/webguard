<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringResponseTimeAggregatePayload implements JsonSerializable
{
    public function __construct(
        public float|int|null $avg,
        public float|int|string|null $min,
        public float|int|string|null $max
    ) {}

    /**
     * @return array{avg: float|int|null, min: float|int|string|null, max: float|int|string|null}
     */
    public function toArray(): array
    {
        return [
            'avg' => $this->avg,
            'min' => $this->min,
            'max' => $this->max,
        ];
    }

    /**
     * @return array{avg: float|int|null, min: float|int|string|null, max: float|int|string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
