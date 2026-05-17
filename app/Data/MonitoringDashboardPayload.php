<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class MonitoringDashboardPayload implements JsonSerializable
{
    /**
     * @param  array{status: string, since: string|null}  $statusSince
     * @param  array<string, mixed>  $statusNow
     * @param  Collection<int, MonitoringIncidentPayload>  $incidents
     * @param  Collection<int, array{date: string, uptime: int, downtime: int, unknown: int}>  $heatmap
     */
    public function __construct(
        public array $statusSince,
        public array $statusNow,
        public MonitoringAvailabilityPayload $uptimeDowntime,
        public MonitoringResponseTimesPayload $responseTimes,
        public Collection $incidents,
        public Collection $heatmap,
        public MonitoringSslPayload $ssl,
        public MonitoringUptimeCalendarPayload $uptimeCalendar
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status_since' => $this->statusSince,
            'status_now' => $this->statusNow,
            'uptime_downtime' => $this->uptimeDowntime->toArray(),
            'response_times' => $this->responseTimes->toArray(),
            'incidents' => $this->incidents
                ->map(static fn (MonitoringIncidentPayload $monitoringIncidentPayload): array => $monitoringIncidentPayload->toArray())
                ->values(),
            'heatmap' => $this->heatmap->values(),
            'ssl' => $this->ssl->toArray(),
            'uptime_calendar' => $this->uptimeCalendar->toArray(),
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
