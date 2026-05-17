<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringUptimeCalendarDayPayload implements JsonSerializable
{
    public function __construct(
        public string $date,
        public ?float $uptimePercentage
    ) {}

    /**
     * @return array{date: string, uptime_percentage: float|null}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'uptime_percentage' => $this->uptimePercentage,
        ];
    }

    /**
     * @return array{date: string, uptime_percentage: float|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
