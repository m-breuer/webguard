<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringPerformanceStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringPerformanceState;
use App\Models\MonitoringResponse;

final class MonitoringPerformanceService
{
    public function reconcile(Monitoring $monitoring, MonitoringResponse $monitoringResponse, MonitoringStatus $monitoringStatus): void
    {
        if (! in_array($monitoring->type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true) || $monitoring->response_time_threshold_ms === null) {
            return;
        }

        $monitoringPerformanceState = MonitoringPerformanceState::query()->firstOrCreate(
            ['monitoring_id' => $monitoring->id],
            ['status' => MonitoringPerformanceStatus::NORMAL, 'consecutive_breaches' => 0],
        );

        if ($monitoringStatus !== MonitoringStatus::UP || $monitoringResponse->response_time === null) {
            return;
        }

        $isSlow = $monitoringResponse->response_time >= $monitoring->response_time_threshold_ms;
        $requiredBreaches = max(1, (int) ($monitoring->response_time_confirmation_threshold ?? 2));

        if ($isSlow) {
            $breaches = $monitoringPerformanceState->consecutive_breaches + 1;
            $monitoringPerformanceState->update(['consecutive_breaches' => $breaches]);

            if ($monitoringPerformanceState->status !== MonitoringPerformanceStatus::DEGRADED && $breaches >= $requiredBreaches) {
                $monitoringPerformanceState->update([
                    'status' => MonitoringPerformanceStatus::DEGRADED,
                    'degraded_at' => now(),
                    'recovered_at' => null,
                ]);
                $this->recordNotification($monitoring, 'DEGRADED');
            }

            return;
        }

        $wasDegraded = $monitoringPerformanceState->status === MonitoringPerformanceStatus::DEGRADED;
        $monitoringPerformanceState->update([
            'status' => MonitoringPerformanceStatus::NORMAL,
            'consecutive_breaches' => 0,
            'recovered_at' => $wasDegraded ? now() : $monitoringPerformanceState->recovered_at,
        ]);

        if ($wasDegraded) {
            $this->recordNotification($monitoring, 'RECOVERED');
        }
    }

    private function recordNotification(Monitoring $monitoring, string $message): void
    {
        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::PERFORMANCE,
            'message' => $message,
            'read' => false,
            'sent' => false,
        ]);
    }
}
