<?php

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

        // Get the last two responses to check for a status change
        $responses = $monitoring->responseResults()->latest()->take(2)->get();

        if ($responses->count() < 2) {
            // Not enough data to determine a change, but if the first status is DOWN, create an incident
            if ($monitoringResponse->status === MonitoringStatus::DOWN) {
                Incident::query()->firstOrCreate(['monitoring_id' => $monitoring->id, 'up_at' => null], ['down_at' => now()]);
            }

            return;
        }

        $latestResponse = $responses->first();
        $previousResponse = $responses->last();

        $statusChanged = $latestResponse->status !== $previousResponse->status;

        if ($statusChanged) {
            if ($latestResponse->status === MonitoringStatus::DOWN) {
                // Status changed to DOWN, create a new incident
                Incident::query()->firstOrCreate(['monitoring_id' => $monitoring->id, 'up_at' => null], ['down_at' => now()]);
            } elseif ($latestResponse->status === MonitoringStatus::UP) {
                // Status changed to UP, close the open incident
                $incident = $monitoring->incidents()->whereNull('up_at')->first();
                if ($incident) {
                    $incident->update(['up_at' => now()]);
                }
            }
        }
    }
}
