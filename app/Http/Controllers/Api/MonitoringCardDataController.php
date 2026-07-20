<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Services\MonitoringHeatmapService;
use App\Services\MonitoringStatusPayloadService;
use BackedEnum;
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
            'ids' => ['nullable', 'array', 'max:100'],
            'ids.*' => ['required', 'string'],
            'summary_ids' => ['nullable', 'array', 'max:100'],
            'summary_ids.*' => ['required', 'string'],
        ]);

        /** @var Collection<int, string> $requestedIds */
        $requestedIds = collect($validated['ids'] ?? [])
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();
        /** @var Collection<int, string> $summaryIds */
        $summaryIds = collect($validated['summary_ids'] ?? $requestedIds->all())
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();

        if ($requestedIds->isEmpty() && $summaryIds->isEmpty()) {
            abort(422, 'At least one monitoring id is required.');
        }
        $allRequestedIds = $requestedIds->merge($summaryIds)->unique()->values();

        $monitorings = Monitoring::query()
            ->select([
                'id',
                'user_id',
                'team_id',
                'status',
                'name',
                'target',
                'type',
                'created_at',
                'maintenance_from',
                'maintenance_until',
                'preferred_location',
                'preferred_locations',
                'heartbeat_interval_minutes',
                'heartbeat_grace_minutes',
                'heartbeat_last_ping_at',
            ])
            ->visibleTo($request->user())
            ->whereIn('id', $allRequestedIds)
            ->with([
                'latestIncident',
                'latestResponseResult',
            ])
            ->get()
            ->keyBy('id');

        $cardMonitorings = $monitorings->only($requestedIds->all());
        $heatmaps = $monitoringHeatmapService->getHeatmapsForMonitorings(
            $cardMonitorings->values(),
            Date::now()->subHours(23)->startOfHour(),
            Date::now()->endOfHour()
        );

        $statusPayloads = $monitorings->mapWithKeys(function (Monitoring $monitoring) use ($monitoringStatusPayloadService): array {
            return [$monitoring->id => $monitoringStatusPayloadService->getPayload($monitoring, includeMonitoring: false)];
        });

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
        });

        $summary = [
            'attention' => 0,
            'healthy' => 0,
            'paused' => 0,
            'maintenance' => 0,
        ];

        foreach ($summaryIds as $summaryId) {
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

        return response()->json(array_filter([
            'data' => $data,
            'summary' => array_key_exists('summary_ids', $validated) ? $summary : null,
        ], static fn (mixed $value): bool => $value !== null));
    }
}
