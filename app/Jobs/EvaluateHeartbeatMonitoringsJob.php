<?php

declare(strict_types=1);

namespace App\Jobs;

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

                    if (! $this->shouldRecordMissedHeartbeat($monitoring)) {
                        continue;
                    }

                    MonitoringResponse::query()->create([
                        'monitoring_id' => $monitoring->id,
                        'http_status_code' => 503,
                        'response_time' => null,
                        'vital_values' => ['heartbeat_overdue' => true],
                    ]);
                }
            });
    }

    private function shouldRecordMissedHeartbeat(Monitoring $monitoring): bool
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
