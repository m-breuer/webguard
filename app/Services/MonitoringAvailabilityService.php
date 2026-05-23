<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringAvailabilityPayload;
use App\Data\MonitoringAvailabilitySegmentPayload;
use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Support\MonitoringResponseHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Date;

class MonitoringAvailabilityService
{
    public function getUptimeDowntime(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $loadAggregatedData = false,
        bool $includeIntradayRawData = true
    ): MonitoringAvailabilityPayload {
        $startDate = $startDate->copy();
        $endDate = $endDate->copy();

        if ($endDate->isFuture()) {
            $endDate = Date::now();
        }

        if ($startDate->gt($endDate)) {
            return $this->buildStats($startDate, $endDate, null, 0, 0, 0, 0, 0, 0, 0);
        }

        if ($loadAggregatedData) {
            return $this->getAggregatedUptimeDowntime($monitoring, $startDate, $endDate, $includeIntradayRawData);
        }

        return $this->getRawUptimeDowntime($monitoring, $startDate, $endDate);
    }

    /**
     * @param  array<int, int>  $days
     * @return array<string, MonitoringAvailabilityPayload>
     */
    public function getUptimeDowntimesForRanges(Monitoring $monitoring, array $days): array
    {
        $normalizedDays = collect($days)
            ->map(static fn (mixed $day): ?int => is_numeric($day) ? $day : null)
            ->filter(static fn (?int $day): bool => $day !== null && $day > 0)
            ->unique()
            ->sort()
            ->values();

        if ($normalizedDays->isEmpty()) {
            return [];
        }

        $now = Date::now();
        $endDate = $now->copy()->endOfDay();
        $requiresRawFallback = $normalizedDays->contains(static fn (int $day): bool => $day <= 1)
            || $monitoring->created_at->diffInDays($now) < 1;

        if ($requiresRawFallback) {
            return $normalizedDays
                ->mapWithKeys(function (int $day) use ($monitoring, $now, $endDate): array {
                    $startDate = $now->copy()->subDays($day)->startOfDay();
                    $loadAggregatedData = $day > 1;
                    $includeIntradayRawData = $day <= 1;

                    if ($monitoring->created_at->diffInDays($now) < 1) {
                        $loadAggregatedData = false;
                    }

                    return [
                        (string) $day => $this->getUptimeDowntime(
                            $monitoring,
                            $startDate,
                            $endDate,
                            $loadAggregatedData,
                            $includeIntradayRawData
                        ),
                    ];
                })
                ->all();
        }

        $trackingStartedAt = $this->getTrackingStartedAtFromDailyResults($monitoring);

        if (! $trackingStartedAt || $trackingStartedAt->gt($endDate)) {
            return $normalizedDays
                ->mapWithKeys(function (int $day) use ($now, $endDate, $trackingStartedAt): array {
                    $startDate = $now->copy()->subDays($day)->startOfDay();

                    return [
                        (string) $day => $this->buildStats(
                            $startDate,
                            $endDate,
                            $trackingStartedAt,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0
                        ),
                    ];
                })
                ->all();
        }

        $maxRangeDays = $normalizedDays->last();
        $globalStartDate = $now->copy()->subDays($maxRangeDays)->startOfDay();

        $dailyResults = $monitoring->dailyResults()
            ->whereBetween('date', [$globalStartDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->get([
                'date',
                'uptime_minutes',
                'downtime_minutes',
                'unknown_minutes',
                'uptime_total',
                'downtime_total',
                'unknown_total',
                'incidents_count',
            ]);

        $rollingTotals = [
            'uptime_minutes' => 0,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'uptime_total' => 0,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'incidents_count' => 0,
        ];
        $dailyResultIndex = 0;
        $dailyResultCount = $dailyResults->count();

        return $normalizedDays
            ->mapWithKeys(function (int $day) use ($dailyResults, $dailyResultCount, &$dailyResultIndex, &$rollingTotals, $endDate, $now, $trackingStartedAt): array {
                $startDate = $now->copy()->subDays($day)->startOfDay();
                $startDateString = $startDate->toDateString();

                while ($dailyResultIndex < $dailyResultCount) {
                    $dailyResult = $dailyResults[$dailyResultIndex];
                    $dailyResultDate = $dailyResult->date->toDateString();

                    if ($dailyResultDate < $startDateString) {
                        break;
                    }

                    $rollingTotals['uptime_minutes'] += (int) $dailyResult->uptime_minutes;
                    $rollingTotals['downtime_minutes'] += (int) $dailyResult->downtime_minutes;
                    $rollingTotals['unknown_minutes'] += (int) $dailyResult->unknown_minutes;
                    $rollingTotals['uptime_total'] += (int) $dailyResult->uptime_total;
                    $rollingTotals['downtime_total'] += (int) $dailyResult->downtime_total;
                    $rollingTotals['unknown_total'] += (int) $dailyResult->unknown_total;
                    $rollingTotals['incidents_count'] += (int) $dailyResult->incidents_count;

                    $dailyResultIndex++;
                }

                return [
                    (string) $day => $this->buildStats(
                        $startDate,
                        $endDate,
                        $trackingStartedAt,
                        $rollingTotals['uptime_minutes'],
                        $rollingTotals['downtime_minutes'],
                        $rollingTotals['unknown_minutes'],
                        $rollingTotals['uptime_total'],
                        $rollingTotals['downtime_total'],
                        $rollingTotals['unknown_total'],
                        $rollingTotals['incidents_count']
                    ),
                ];
            })
            ->all();
    }

    public function countIncidents(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): int
    {
        return (int) $monitoring->incidents()
            ->whereBetween('down_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->count();
    }

    public function getAggregatedIncidentsCount(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $includeIntradayRawData = true
    ): int {
        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->endOfDay();

        if ($endDate->isFuture()) {
            $endDate = Date::now();
        }

        if ($startDate->gt($endDate)) {
            return 0;
        }

        $today = Date::today();
        $historicalEndDate = $includeIntradayRawData
            ? $endDate->copy()->min($today->copy()->subDay()->endOfDay())
            : $endDate->copy();

        $incidentsCount = 0;

        if ($startDate->lte($historicalEndDate)) {
            $incidentsCount += (int) $monitoring->dailyResults()
                ->whereBetween('date', [$startDate->toDateString(), $historicalEndDate->toDateString()])
                ->sum('incidents_count');
        }

        if (! $includeIntradayRawData || $endDate->lt($today)) {
            return $incidentsCount;
        }

        $liveStartDate = $startDate->copy()->max($today);

        if ($liveStartDate->gt($endDate)) {
            return $incidentsCount;
        }

        return $incidentsCount + $this->countIncidents($monitoring, $liveStartDate, $endDate);
    }

    private function getAggregatedUptimeDowntime(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $includeIntradayRawData = true
    ): MonitoringAvailabilityPayload {
        $trackingStartedAt = $includeIntradayRawData
            ? $this->getTrackingStartedAt($monitoring)
            : $this->getTrackingStartedAtFromDailyResults($monitoring);

        if (! $trackingStartedAt || $trackingStartedAt->gt($endDate)) {
            return $this->buildStats($startDate, $endDate, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        $uptimeMinutes = 0;
        $downtimeMinutes = 0;
        $unknownMinutes = 0;
        $uptimeTotal = 0;
        $downtimeTotal = 0;
        $unknownTotal = 0;
        $incidentsCount = 0;

        $today = Date::today();
        $historicalEndDate = $includeIntradayRawData
            ? $endDate->copy()->min($today->copy()->subDay()->endOfDay())
            : $endDate->copy();

        if ($startDate->lte($historicalEndDate)) {
            $aggregatedData = $monitoring->dailyResults()
                ->whereBetween('date', [$startDate->toDateString(), $historicalEndDate->toDateString()])
                ->selectRaw('
                    SUM(uptime_minutes) as uptime_minutes,
                    SUM(downtime_minutes) as downtime_minutes,
                    SUM(unknown_minutes) as unknown_minutes,
                    SUM(uptime_total) as uptime_total,
                    SUM(downtime_total) as downtime_total,
                    SUM(unknown_total) as unknown_total,
                    SUM(incidents_count) as incidents_count
                ')
                ->first();

            $uptimeMinutes += (int) ($aggregatedData->uptime_minutes ?? 0);
            $downtimeMinutes += (int) ($aggregatedData->downtime_minutes ?? 0);
            $unknownMinutes += (int) ($aggregatedData->unknown_minutes ?? 0);
            $uptimeTotal += (int) ($aggregatedData->uptime_total ?? 0);
            $downtimeTotal += (int) ($aggregatedData->downtime_total ?? 0);
            $unknownTotal += (int) ($aggregatedData->unknown_total ?? 0);
            $incidentsCount += (int) ($aggregatedData->incidents_count ?? 0);
        }

        if ($includeIntradayRawData && $endDate->gte($today)) {
            $liveStartDate = $startDate->copy()->max($today);
            $liveUptimeDowntime = $this->getRawUptimeDowntime($monitoring, $liveStartDate, $endDate);

            $uptimeMinutes += $liveUptimeDowntime->uptime->minutes;
            $downtimeMinutes += $liveUptimeDowntime->downtime->minutes;
            $unknownMinutes += $liveUptimeDowntime->unknown->minutes;
            $uptimeTotal += $liveUptimeDowntime->uptime->total;
            $downtimeTotal += $liveUptimeDowntime->downtime->total;
            $unknownTotal += $liveUptimeDowntime->unknown->total;
            $incidentsCount += $liveUptimeDowntime->downtime->incidentsCount ?? 0;
        }

        return $this->buildStats(
            $startDate,
            $endDate,
            $trackingStartedAt,
            $uptimeMinutes,
            $downtimeMinutes,
            $unknownMinutes,
            $uptimeTotal,
            $downtimeTotal,
            $unknownTotal,
            $incidentsCount
        );
    }

    private function getRawUptimeDowntime(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): MonitoringAvailabilityPayload
    {
        $trackingStartedAt = $this->getTrackingStartedAt($monitoring);

        if (! $trackingStartedAt || $trackingStartedAt->gt($endDate)) {
            return $this->buildStats($startDate, $endDate, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        $effectiveStartDate = $startDate->copy()->max($trackingStartedAt);
        $effectiveEndDate = $endDate->copy();

        if ($effectiveStartDate->gte($effectiveEndDate)) {
            return $this->buildStats($startDate, $endDate, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        $builder = MonitoringResponseHistory::queryForEndDate($endDate)
            ->where('monitoring_id', $monitoring->id);

        $statusAtStart = (clone $builder)
            ->where('created_at', '<=', $effectiveStartDate)->latest()
            ->orderByDesc('id')
            ->value('status');

        $responses = (clone $builder)
            ->select(['id', 'status', 'created_at'])
            ->whereBetween('created_at', [$effectiveStartDate, $effectiveEndDate])->oldest()
            ->orderBy('id')
            ->get();

        $overallUptimeMinutes = 0;
        $overallDowntimeMinutes = 0;
        $overallUnknownMinutes = 0;
        $cursor = $effectiveStartDate->copy();
        $currentStatus = $statusAtStart;

        foreach ($responses as $response) {
            $responseTimestamp = Date::parse($response->created_at);

            if ($responseTimestamp->gt($cursor)) {
                $segmentMinutes = (int) $cursor->diffInMinutes($responseTimestamp);
                $this->incrementMinutesByStatus(
                    $currentStatus,
                    $segmentMinutes,
                    $overallUptimeMinutes,
                    $overallDowntimeMinutes,
                    $overallUnknownMinutes
                );

                $cursor = $responseTimestamp;
            }

            $currentStatus = $response->status instanceof MonitoringStatus
                ? $response->status->value
                : (string) $response->status;
        }

        if ($cursor->lt($effectiveEndDate)) {
            $segmentMinutes = (int) $cursor->diffInMinutes($effectiveEndDate);
            $this->incrementMinutesByStatus(
                $currentStatus,
                $segmentMinutes,
                $overallUptimeMinutes,
                $overallDowntimeMinutes,
                $overallUnknownMinutes
            );
        }

        $data = (clone $builder)
            ->selectRaw("
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as uptime_total,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as downtime_total,
                SUM(CASE WHEN status NOT IN ('up', 'down') THEN 1 ELSE 0 END) as unknown_total
            ")
            ->whereBetween('created_at', [$effectiveStartDate, $effectiveEndDate])
            ->first();

        $incidentsCount = $this->countOverlappingIncidents($monitoring, $effectiveStartDate, $effectiveEndDate);

        return $this->buildStats(
            $startDate,
            $endDate,
            $trackingStartedAt,
            $overallUptimeMinutes,
            $overallDowntimeMinutes,
            $overallUnknownMinutes,
            (int) ($data->uptime_total ?? 0),
            (int) ($data->downtime_total ?? 0),
            (int) ($data->unknown_total ?? 0),
            $incidentsCount
        );
    }

    private function incrementMinutesByStatus(
        string|MonitoringStatus|null $status,
        int $minutes,
        int &$uptimeMinutes,
        int &$downtimeMinutes,
        int &$unknownMinutes
    ): void {
        if ($minutes <= 0) {
            return;
        }

        $statusValue = $status instanceof MonitoringStatus
            ? $status->value
            : $status;

        match ($statusValue) {
            MonitoringStatus::UP->value => $uptimeMinutes += $minutes,
            MonitoringStatus::DOWN->value => $downtimeMinutes += $minutes,
            default => $unknownMinutes += $minutes,
        };
    }

    private function countOverlappingIncidents(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): int
    {
        return (int) $monitoring->incidents()
            ->where('down_at', '<=', $endDate)
            ->where(function (Builder $builder) use ($startDate) {
                $builder->where('up_at', '>=', $startDate)
                    ->orWhereNull('up_at');
            })
            ->count();
    }

    private function buildStats(
        Carbon $startDate,
        Carbon $endDate,
        ?Carbon $trackingStartedAt,
        int $uptimeMinutes,
        int $downtimeMinutes,
        int $unknownMinutes,
        int $uptimeTotal,
        int $downtimeTotal,
        int $unknownTotal,
        int $incidentsCount
    ): MonitoringAvailabilityPayload {
        $totalTrackedMinutes = $uptimeMinutes + $downtimeMinutes + $unknownMinutes;
        $hasData = $totalTrackedMinutes > 0;

        return new MonitoringAvailabilityPayload(
            from: $startDate,
            to: $endDate,
            hasData: $hasData,
            trackingStartedAt: $trackingStartedAt?->toIso8601String(),
            uptime: new MonitoringAvailabilitySegmentPayload(
                minutes: $uptimeMinutes,
                percentage: $hasData ? ($uptimeMinutes / $totalTrackedMinutes) * 100 : null,
                total: $uptimeTotal
            ),
            downtime: new MonitoringAvailabilitySegmentPayload(
                minutes: $downtimeMinutes,
                percentage: $hasData ? ($downtimeMinutes / $totalTrackedMinutes) * 100 : null,
                total: $downtimeTotal,
                incidentsCount: $incidentsCount
            ),
            unknown: new MonitoringAvailabilitySegmentPayload(
                minutes: $unknownMinutes,
                percentage: $hasData ? ($unknownMinutes / $totalTrackedMinutes) * 100 : null,
                total: $unknownTotal
            )
        );
    }

    private function getTrackingStartedAt(Monitoring $monitoring): ?Carbon
    {
        $trackingStartedAt = collect([
            $monitoring->archivedResponseResults()->min('created_at'),
            $monitoring->responseResults()->min('created_at'),
        ])->filter()->map(fn ($date): Carbon => Date::parse($date))->sort()->first();

        return $trackingStartedAt instanceof Carbon ? $trackingStartedAt : null;
    }

    private function getTrackingStartedAtFromDailyResults(Monitoring $monitoring): ?Carbon
    {
        $trackingStartedAt = $monitoring->dailyResults()->min('date');

        if (! $trackingStartedAt) {
            return null;
        }

        return Date::parse($trackingStartedAt)->startOfDay();
    }
}
