<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class EvaluateHeartbeatMonitoringsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct()
    {
        $this->onConnection('redis');
        $this->onQueue((string) config('monitoring.heartbeat_queue', 'heartbeat'));
    }

    public function uniqueId(): string
    {
        return 'heartbeat-monitoring-evaluation';
    }

    public function handle(): void
    {
        Monitoring::query()
            ->where('type', MonitoringType::HEARTBEAT->value)
            ->where('status', 'active')
            ->whereNotNull('heartbeat_interval_minutes')
            ->with('latestResponseResult')
            ->chunkById(100, function (Collection $monitorings): void {
                foreach ($monitorings as $monitoring) {
                    if ($monitoring->isUnderMaintenance()) {
                        continue;
                    }

                    $referenceTimestamp = $monitoring->heartbeat_last_ping_at ?? $monitoring->created_at;
                    $heartbeatIntervalMinutes = (int) ($monitoring->heartbeat_interval_minutes ?? 0);
                    $heartbeatGraceMinutes = (int) ($monitoring->heartbeat_grace_minutes ?? 0);

                    if ($heartbeatIntervalMinutes < 1) {
                        continue;
                    }

                    $dueAt = $referenceTimestamp
                        ->copy()
                        ->addMinutes($heartbeatIntervalMinutes + $heartbeatGraceMinutes);

                    if (now()->lte($dueAt)) {
                        continue;
                    }

                    if ($monitoring->latestResponseResult?->status === MonitoringStatus::DOWN) {
                        continue;
                    }

                    MonitoringResponse::query()->create([
                        'monitoring_id' => $monitoring->id,
                        'status' => MonitoringStatus::DOWN,
                        'http_status_code' => 503,
                        'response_time' => null,
                    ]);
                }
            });
    }
}
