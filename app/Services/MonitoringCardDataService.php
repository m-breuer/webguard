<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitoring;
use App\Models\User;
use App\Queries\MonitoringCardQuery;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class MonitoringCardDataService
{
    public function __construct(
        private readonly MonitoringCardQuery $monitoringCardQuery,
        private readonly MonitoringStatusPayloadService $monitoringStatusPayloadService,
        private readonly MonitoringHeatmapService $monitoringHeatmapService,
    ) {}

    /**
     * @param  Collection<int, string>  $requestedIds
     * @param  Collection<int, string>  $summaryIds
     * @return array{data: array<string, array<string, mixed>>, summary?: array{attention:int,healthy:int,paused:int,maintenance:int}}
     */
    public function for(User $user, Collection $requestedIds, Collection $summaryIds, bool $includeSummary): array
    {
        $monitorings = $this->monitoringCardQuery
            ->for($user, $requestedIds->merge($summaryIds)->unique()->values()->all())
            ->keyBy('id');

        $cardMonitorings = $monitorings->only($requestedIds->all());
        $heatmaps = $this->monitoringHeatmapService->getHeatmapsForMonitorings(
            $cardMonitorings->values(),
            Date::now()->subHours(23)->startOfHour(),
            Date::now()->endOfHour(),
        );

        $statusPayloads = $monitorings->mapWithKeys(
            fn (Monitoring $monitoring): array => [
                $monitoring->id => $this->monitoringStatusPayloadService->getPayload($monitoring, includeMonitoring: false),
            ]
        );

        $data = $requestedIds->mapWithKeys(function (string $monitoringId) use ($monitorings, $heatmaps, $statusPayloads): array {
            /** @var Monitoring|null $monitoring */
            $monitoring = $monitorings->get($monitoringId);

            if (! $monitoring) {
                return [];
            }

            return [
                $monitoringId => array_merge(
                    $statusPayloads->get($monitoringId)->toArray(),
                    ['heatmap' => $heatmaps[$monitoringId] ?? []]
                ),
            ];
        })->all();

        $payload = ['data' => $data];

        if ($includeSummary) {
            $payload['summary'] = $this->summary($summaryIds, $monitorings, $statusPayloads);
        }

        return $payload;
    }

    /**
     * @param  Collection<int, string>  $summaryIds
     * @param  Collection<string, Monitoring>  $monitorings
     * @param  Collection<string, mixed>  $statusPayloads
     * @return array{attention:int,healthy:int,paused:int,maintenance:int}
     */
    private function summary(Collection $summaryIds, Collection $monitorings, Collection $statusPayloads): array
    {
        $summary = [
            'attention' => 0,
            'healthy' => 0,
            'paused' => 0,
            'maintenance' => 0,
        ];

        foreach ($summaryIds as $summaryId) {
            /** @var Monitoring|null $monitoring */
            $monitoring = $monitorings->get($summaryId);

            if (! $monitoring) {
                continue;
            }

            $status = $statusPayloads->get($summaryId)->status;
            $statusValue = $status instanceof BackedEnum ? $status->value : $status;

            if (in_array($statusValue, ['down', 'unknown'], true)) {
                $summary['attention']++;
            }

            if ($statusValue === 'up') {
                $summary['healthy']++;
            }

            if ($monitoring->isPaused()) {
                $summary['paused']++;
            }

            if ($monitoring->isUnderMaintenance()) {
                $summary['maintenance']++;
            }
        }

        return $summary;
    }
}
