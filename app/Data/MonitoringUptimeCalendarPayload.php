<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class MonitoringUptimeCalendarPayload implements JsonSerializable
{
    /**
     * @param  Collection<string, MonitoringUptimeCalendarMonthPayload>  $months
     */
    public function __construct(
        public Collection $months
    ) {}

    /**
     * @return array<string, array{days: list<array{date: string, uptime_percentage: float|null}>, monthly_average_uptime: float|null}>
     */
    public function toArray(): array
    {
        return $this->months
            ->map(static fn (MonitoringUptimeCalendarMonthPayload $monitoringUptimeCalendarMonthPayload): array => $monitoringUptimeCalendarMonthPayload->toArray())
            ->all();
    }

    /**
     * @return array<string, array{days: list<array{date: string, uptime_percentage: float|null}>, monthly_average_uptime: float|null}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
