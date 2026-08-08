<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Services\MonitoringHealthEvaluator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class EvaluateServerHealthMonitoringsJob implements ShouldBeUnique, ShouldQueue
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
        return 'server-health-monitoring-evaluation';
    }

    public function handle(): void
    {
        Monitoring::query()
            ->where('type', MonitoringType::SERVER_HEALTH->value)
            ->where('status', MonitoringLifecycleStatus::ACTIVE->value)
            ->with('latestResponseResult')
            ->chunkById(100, function (Collection $monitorings): void {
                foreach ($monitorings as $monitoring) {
                    if ($monitoring->isUnderMaintenance()) {
                        continue;
                    }

                    $referenceTimestamp = $monitoring->server_health_last_reported_at ?? $monitoring->created_at;
                    $intervalMinutes = (int) ($monitoring->server_health_report_interval_minutes ?? 1);
                    $graceMinutes = (int) ($monitoring->server_health_grace_minutes ?? 5);

                    if ($intervalMinutes < 1 || now()->lte($referenceTimestamp->copy()->addMinutes($intervalMinutes + $graceMinutes))) {
                        continue;
                    }

                    if (! $this->shouldRecordStaleReport($monitoring)) {
                        continue;
                    }

                    MonitoringResponse::query()->create([
                        'monitoring_id' => $monitoring->id,
                        'status' => MonitoringStatus::DOWN,
                        'http_status_code' => null,
                        'response_time' => null,
                        'vital_values' => ['server_health_report_stale' => true],
                    ]);
                }
            });
    }

    private function shouldRecordStaleReport(Monitoring $monitoring): bool
    {
        $monitoringHealthEvaluator = resolve(MonitoringHealthEvaluator::class);

        if ($monitoring->latestResponseResult === null || $monitoringHealthEvaluator->availabilityFor($monitoring, $monitoring->latestResponseResult) !== MonitoringStatus::DOWN) {
            return true;
        }

        if ($monitoring->incidents()->whereNull('up_at')->exists()) {
            return false;
        }

        $threshold = max(1, (int) ($monitoring->failure_confirmation_threshold ?? 1));
        $responses = $monitoring->responseResults()
            ->latest()
            ->orderByDesc('id')
            ->take($threshold)
            ->get();

        if ($responses->count() < $threshold) {
            return true;
        }

        return $responses->contains(fn (MonitoringResponse $monitoringResponse): bool => $monitoringHealthEvaluator->availabilityFor($monitoring, $monitoringResponse) !== MonitoringStatus::DOWN);
    }
}
