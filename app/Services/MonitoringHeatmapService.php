<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitoring;
use App\Support\MonitoringResponseHistory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringHeatmapService
{
    public function __construct(private readonly MonitoringStatsCache $monitoringStatsCache) {}

    /**
     * @return Collection<int, array{date: Carbon, uptime: int, downtime: int, unknown: int}>
     */
    public function getHeatmap(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): Collection
    {
        $heatmaps = $this->getHeatmapsForMonitorings(collect([$monitoring]), $startDate, $endDate);

        return collect($heatmaps[$monitoring->id] ?? []);
    }

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     * @return array<string, list<array{date: Carbon, uptime: int, downtime: int, unknown: int}>>
     */
    public function getHeatmapsForMonitorings(Collection $monitorings, Carbon $startDate, Carbon $endDate): array
    {
        if ($monitorings->isEmpty()) {
            return [];
        }

        // Heatmap payloads always represent the latest 24 hourly buckets.
        $startDate = Date::now()->subHours(23)->startOfHour();
        $endDate = Date::now()->endOfHour();

        $heatmaps = [];
        $monitoringsToLoad = collect();

        foreach ($monitorings as $monitoring) {
            $cachedHeatmap = $this->monitoringStatsCache->get(
                $monitoring,
                $this->monitoringStatsCache->heatmapKey($monitoring)
            );

            if (is_array($cachedHeatmap)) {
                $heatmaps[$monitoring->id] = $cachedHeatmap;

                continue;
            }

            $monitoringsToLoad->push($monitoring);
        }

        if ($monitoringsToLoad->isEmpty()) {
            return $heatmaps;
        }

        $monitoringIds = $monitoringsToLoad
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values();

        $interval = (int) config('monitoring.interval', 5);
        $periodExpression = MonitoringResponseHistory::periodExpression('created_at', '%Y-%m-%d %H');

        $rawByMonitoring = MonitoringResponseHistory::queryForEndDate($endDate)
            ->whereIn('monitoring_id', $monitoringIds)
            ->selectRaw("monitoring_id, {$periodExpression} as period,
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) * {$interval} as uptime,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) * {$interval} as downtime,
                SUM(CASE WHEN status NOT IN ('up', 'down') THEN 1 ELSE 0 END) * {$interval} as unknown
            ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('monitoring_id', 'period')
            ->orderBy('period')
            ->get()
            ->groupBy('monitoring_id')
            ->map(static fn (Collection $rows): Collection => $rows->keyBy('period'));

        $freshHeatmaps = $monitoringIds
            ->mapWithKeys(function (string $monitoringId) use ($rawByMonitoring, $startDate, $endDate): array {
                /** @var Collection<int, object> $raw */
                $raw = $rawByMonitoring->get($monitoringId, collect());

                $heatmap = collect(CarbonPeriod::create($startDate, '1 hour', $endDate))
                    ->map(function (Carbon $hour) use ($raw): array {
                        $record = $raw->get($hour->format('Y-m-d H'));

                        return [
                            'date' => $hour,
                            'uptime' => (int) ($record->uptime ?? 0),
                            'downtime' => (int) ($record->downtime ?? 0),
                            'unknown' => (int) ($record->unknown ?? 0),
                        ];
                    })
                    ->values()
                    ->all();

                return [$monitoringId => $heatmap];
            })
            ->all();

        foreach ($freshHeatmaps as $monitoringId => $heatmap) {
            $monitoring = $monitoringsToLoad->firstWhere('id', $monitoringId);

            if ($monitoring) {
                $this->monitoringStatsCache->put(
                    $monitoring,
                    $this->monitoringStatsCache->heatmapKey($monitoring),
                    $heatmap,
                    $this->monitoringStatsCache->heatmapExpiresAt()
                );
            }

            $heatmaps[$monitoringId] = $heatmap;
        }

        return $heatmaps;
    }
}
