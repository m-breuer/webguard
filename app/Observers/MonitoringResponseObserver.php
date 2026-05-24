<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\MonitoringResponse;

class MonitoringResponseObserver
{
    /**
     * Handle the MonitoringResponse "created" event.
     */
    public function created(MonitoringResponse $monitoringResponse): void
    {
        $monitoring = $monitoringResponse->monitoring;

        $threshold = max(1, (int) ($monitoring->failure_confirmation_threshold ?? 1));
        $responses = $monitoring->responseResults()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(max(2, $threshold))
            ->get();

        $latestResponse = $responses->first();

        if ($latestResponse?->status === MonitoringStatus::DOWN) {
            if ($this->hasConfirmedFailure($monitoringResponse)) {
                $this->openIncident($monitoringResponse);
            }

            return;
        }

        if ($latestResponse?->status === MonitoringStatus::UP) {
            // Status changed to UP, close the open incident
            $incident = $monitoring->incidents()->whereNull('up_at')->first();
            if ($incident) {
                $incident->update(['up_at' => now()]);
            }
        }
    }

    private function hasConfirmedFailure(MonitoringResponse $monitoringResponse): bool
    {
        $monitoring = $monitoringResponse->monitoring;
        $threshold = max(1, (int) ($monitoring->failure_confirmation_threshold ?? 1));

        $responses = $monitoring->responseResults()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($threshold)
            ->get();

        return $responses->count() >= $threshold
            && $responses->every(static fn (MonitoringResponse $response): bool => $response->status === MonitoringStatus::DOWN);
    }

    private function openIncident(MonitoringResponse $monitoringResponse): void
    {
        Incident::query()->firstOrCreate(
            ['monitoring_id' => $monitoringResponse->monitoring_id, 'up_at' => null],
            ['down_at' => now()]
        );
    }
}
