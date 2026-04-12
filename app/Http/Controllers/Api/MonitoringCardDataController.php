<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Services\MonitoringResultService;
use App\Support\MonitoringStatusMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringCardDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:25'],
            'ids.*' => ['required', 'string'],
        ]);

        /** @var Collection<int, string> $requestedIds */
        $requestedIds = collect($validated['ids'])
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();

        $monitorings = Monitoring::query()
            ->select([
                'id',
                'name',
                'target',
                'type',
                'created_at',
                'maintenance_from',
                'maintenance_until',
            ])
            ->whereIn('id', $requestedIds)
            ->with([
                'latestIncident',
                'latestResponseResult',
            ])
            ->get()
            ->keyBy('id');

        $heatmaps = MonitoringResultService::getHeatmapsForMonitorings(
            $monitorings->values(),
            Date::now()->subHours(23)->startOfHour(),
            Date::now()->endOfHour()
        );

        $data = $requestedIds->mapWithKeys(function (string $monitoringId) use ($monitorings, $heatmaps): array {
            /** @var Monitoring|null $monitoring */
            $monitoring = $monitorings->get($monitoringId);

            if (! $monitoring) {
                return [];
            }

            $statusSince = MonitoringResultService::getStatusSince($monitoring);
            $statusNow = MonitoringResultService::getStatusNow($monitoring);
            $latestStatusCode = $monitoring->latestResponseResult?->http_status_code;
            $maintenanceActive = $monitoring->isUnderMaintenance();

            return [
                $monitoringId => array_merge($statusSince, $statusNow, [
                    'status_code' => $latestStatusCode,
                    'status_changed_at' => $statusSince['since'] ?? null,
                    'status_identifier' => MonitoringStatusMeta::statusIdentifier($latestStatusCode, $maintenanceActive),
                    'status_key' => MonitoringStatusMeta::statusKey($latestStatusCode, $maintenanceActive),
                    'heatmap' => $heatmaps[$monitoringId] ?? [],
                ]),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
