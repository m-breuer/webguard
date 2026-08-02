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
    public function reconcile(Monitoring $monitoring, MonitoringResponse $response, MonitoringStatus $availability): void
    {
        if (! in_array($monitoring->type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true) || $monitoring->response_time_threshold_ms === null) {
            return;
        }

        $state = MonitoringPerformanceState::query()->firstOrCreate(
            ['monitoring_id' => $monitoring->id],
            ['status' => MonitoringPerformanceStatus::NORMAL, 'consecutive_breaches' => 0],
        );

        if ($availability !== MonitoringStatus::UP || $response->response_time === null) {
            return;
        }

        $isSlow = $response->response_time >= $monitoring->response_time_threshold_ms;
        $requiredBreaches = max(1, (int) ($monitoring->response_time_confirmation_threshold ?? 2));

        if ($isSlow) {
            $breaches = $state->consecutive_breaches + 1;
            $state->update(['consecutive_breaches' => $breaches]);

            if ($state->status !== MonitoringPerformanceStatus::DEGRADED && $breaches >= $requiredBreaches) {
                $state->update([
                    'status' => MonitoringPerformanceStatus::DEGRADED,
                    'degraded_at' => now(),
                    'recovered_at' => null,
                ]);
                $this->recordNotification($monitoring, 'DEGRADED');
            }

            return;
        }

        $wasDegraded = $state->status === MonitoringPerformanceStatus::DEGRADED;
        $state->update([
            'status' => MonitoringPerformanceStatus::NORMAL,
            'consecutive_breaches' => 0,
            'recovered_at' => $wasDegraded ? now() : $state->recovered_at,
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
