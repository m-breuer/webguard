<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringIncidentPayload;
use App\Models\Monitoring;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringIncidentService
{
    /**
     * @return Collection<int, MonitoringIncidentPayload>
     */
    public function getIncidents(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): Collection
    {
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();

        return $monitoring->incidents()
            ->whereBetween('down_at', [$startDate, $endDate])
            ->select('down_at', 'up_at')
            ->latest('down_at')
            ->get()
            ->map(function ($incident): MonitoringIncidentPayload {
                $downAt = Date::parse($incident->down_at);
                $upAt = $incident->up_at ? Date::parse($incident->up_at) : null;

                return new MonitoringIncidentPayload(
                    downAt: $downAt->toIso8601String(),
                    upAt: $upAt?->toIso8601String()
                );
            });
    }
}
