<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Services\MonitoringHeatmapService;
use App\Services\MonitoringStatusPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringCardDataController extends Controller
{
    public function __invoke(
        Request $request,
        MonitoringStatusPayloadService $monitoringStatusPayloadService,
        MonitoringHeatmapService $monitoringHeatmapService
    ): JsonResponse {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
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
                'user_id',
                'name',
                'target',
                'type',
                'created_at',
                'maintenance_from',
                'maintenance_until',
            ])
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $requestedIds)
            ->with([
                'latestIncident',
                'latestResponseResult',
            ])
            ->get()
            ->keyBy('id');

        $heatmaps = $monitoringHeatmapService->getHeatmapsForMonitorings(
            $monitorings->values(),
            Date::now()->subHours(23)->startOfHour(),
            Date::now()->endOfHour()
        );

        $data = $requestedIds->mapWithKeys(function (string $monitoringId) use ($monitorings, $heatmaps, $monitoringStatusPayloadService): array {
            /** @var Monitoring|null $monitoring */
            $monitoring = $monitorings->get($monitoringId);

            if (! $monitoring) {
                return [];
            }

            return [
                $monitoringId => array_merge(
                    $monitoringStatusPayloadService->getPayload($monitoring, includeMonitoring: false)->toArray(),
                    ['heatmap' => $heatmaps[$monitoringId] ?? []]
                ),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
