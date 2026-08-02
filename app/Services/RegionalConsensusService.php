<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringStatus;
use App\Enums\RegionalConsensusStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class RegionalConsensusService
{
    public function __construct(private readonly MonitoringHealthEvaluator $monitoringHealthEvaluator) {}

    /**
     * @return array{status: RegionalConsensusStatus, total_locations: int, reporting_locations: int, required_failures: int, affected_locations: list<string>, locations: list<array{code: string, status: string, checked_at: string|null}>}
     */
    public function snapshot(Monitoring $monitoring): array
    {
        $locations = $monitoring->preferredLocationCodes();
        $requiredFailures = intdiv(count($locations), 2) + 1;
        $freshAfter = Date::now()->subMinutes(max(1, (int) config('monitoring.regional_consensus_freshness_minutes', 10)));

        $latestByLocation = $this->latestResponsesByLocation($monitoring, $locations)
            ->filter(static fn (MonitoringResponse $monitoringResponse): bool => $monitoringResponse->created_at->gte($freshAfter));

        $affectedLocations = $latestByLocation
            ->filter(fn (MonitoringResponse $monitoringResponse): bool => $this->monitoringHealthEvaluator->availabilityFor($monitoring, $monitoringResponse) === MonitoringStatus::DOWN)
            ->keys()
            ->values()
            ->all();

        $regionalConsensusStatus = $this->classify(count($locations), count($affectedLocations), $latestByLocation->count(), $requiredFailures);

        return [
            'status' => $regionalConsensusStatus,
            'total_locations' => count($locations),
            'reporting_locations' => $latestByLocation->count(),
            'required_failures' => $requiredFailures,
            'affected_locations' => $affectedLocations,
            'locations' => collect($locations)->map(function (string $location) use ($latestByLocation, $monitoring): array {
                $response = $latestByLocation->get($location);

                return [
                    'code' => $location,
                    'status' => $response ? $this->monitoringHealthEvaluator->availabilityFor($monitoring, $response)->value : MonitoringStatus::UNKNOWN->value,
                    'checked_at' => $response?->created_at->toIso8601String(),
                ];
            })->all(),
        ];
    }

    public function reconcile(Monitoring $monitoring): void
    {
        if (count($monitoring->preferredLocationCodes()) < 2) {
            return;
        }

        $snapshot = $this->snapshot($monitoring);
        $openIncident = $monitoring->incidents()->whereNull('up_at')->first();
        $isIncident = in_array($snapshot['status'], [RegionalConsensusStatus::REGIONAL, RegionalConsensusStatus::GLOBAL], true);

        if ($isIncident) {
            if ($openIncident) {
                $openIncident->update([
                    'consensus_status' => $snapshot['status'],
                    'affected_locations' => $snapshot['affected_locations'],
                ]);

                return;
            }

            Incident::query()->create([
                'monitoring_id' => $monitoring->id,
                'consensus_status' => $snapshot['status'],
                'affected_locations' => $snapshot['affected_locations'],
                'down_at' => now(),
            ]);

            return;
        }

        if ($openIncident && in_array($snapshot['status'], [RegionalConsensusStatus::HEALTHY, RegionalConsensusStatus::LOCALIZED], true)) {
            $openIncident->update(['up_at' => now()]);
        }
    }

    private function classify(int $total, int $failed, int $reporting, int $requiredFailures): RegionalConsensusStatus
    {
        if ($total < 2) {
            return RegionalConsensusStatus::UNKNOWN;
        }

        if ($failed > 0 && $failed < $requiredFailures) {
            return RegionalConsensusStatus::LOCALIZED;
        }

        if ($reporting < $requiredFailures) {
            return RegionalConsensusStatus::UNKNOWN;
        }

        if ($failed === 0) {
            return RegionalConsensusStatus::HEALTHY;
        }

        return $failed === $total ? RegionalConsensusStatus::GLOBAL : RegionalConsensusStatus::REGIONAL;
    }

    /**
     * @param  list<string>  $locations
     * @return Collection<string, MonitoringResponse>
     */
    private function latestResponsesByLocation(Monitoring $monitoring, array $locations): Collection
    {
        return $monitoring->responseResults()
            ->whereIn('location_code', $locations)
            ->latest('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('location_code')
            ->keyBy(fn (MonitoringResponse $monitoringResponse): string => (string) $monitoringResponse->location_code);
    }
}
