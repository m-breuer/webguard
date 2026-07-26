<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use Illuminate\Support\Facades\Date;

final class MonitoringStateResolver
{
    public function status(Monitoring $monitoring): string
    {
        if ($monitoring->isPaused()) {
            return MonitoringLifecycleStatus::PAUSED->value;
        }

        if ($monitoring->isUnderMaintenance()) {
            return 'maintenance';
        }

        if ($monitoring->latestIncident?->up_at === null && $monitoring->latestIncident !== null) {
            return MonitoringStatus::DOWN->value;
        }

        $latestResponse = $monitoring->latestResponseResult;
        if ($latestResponse === null || $this->isStale($monitoring)) {
            return MonitoringStatus::UNKNOWN->value;
        }

        return $latestResponse->status->value;
    }

    private function isStale(Monitoring $monitoring): bool
    {
        $latestResponse = $monitoring->latestResponseResult;
        if ($latestResponse === null) {
            return false;
        }

        $intervalMinutes = $monitoring->isHeartbeat()
            ? ((int) ($monitoring->heartbeat_interval_minutes ?? 0) + (int) ($monitoring->heartbeat_grace_minutes ?? 0))
            : (int) config('monitoring.interval', 5);

        return $latestResponse->created_at->lt(Date::now()->subMinutes(max(10, $intervalMinutes * 3)));
    }
}
