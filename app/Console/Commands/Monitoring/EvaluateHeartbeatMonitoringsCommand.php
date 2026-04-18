<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class EvaluateHeartbeatMonitoringsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'monitoring:evaluate-heartbeats';

    /**
     * @var string
     */
    protected $description = 'Marks heartbeat monitorings as down when expected pings are overdue.';

    public function handle(): int
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

        return Command::SUCCESS;
    }
}
