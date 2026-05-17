<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringResponseTimePointPayload implements JsonSerializable
{
    public function __construct(
        public string $date,
        public float|int|string|null $avg,
        public float|int|string|null $min,
        public float|int|string|null $max
    ) {}

    /**
     * @return array{date: string, avg: float|int|string|null, min: float|int|string|null, max: float|int|string|null}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'avg' => $this->avg,
            'min' => $this->min,
            'max' => $this->max,
        ];
    }

    /**
     * @return array{date: string, avg: float|int|string|null, min: float|int|string|null, max: float|int|string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
