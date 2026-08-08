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
        $responseTimeThreshold = $this->responseTimeThresholdFor($monitoring);

        if ($responseTimeThreshold === null) {
            return;
        }

        $monitoringPerformanceState = MonitoringPerformanceState::query()->firstOrCreate(
            ['monitoring_id' => $monitoring->id],
            ['status' => MonitoringPerformanceStatus::NORMAL, 'consecutive_breaches' => 0],
        );

        $responseTime = $this->observedResponseTime($monitoring, $monitoringResponse);

        if ($monitoringStatus !== MonitoringStatus::UP || $responseTime === null) {
            return;
        }

        $isSlow = $responseTime >= $responseTimeThreshold;
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

    private function responseTimeThresholdFor(Monitoring $monitoring): ?int
    {
        return match ($monitoring->type) {
            MonitoringType::HTTP, MonitoringType::KEYWORD => $monitoring->response_time_threshold_ms,
            MonitoringType::SERVER_HEALTH => $monitoring->server_health_service_response_time_threshold_ms,
            default => null,
        };
    }

    private function observedResponseTime(Monitoring $monitoring, MonitoringResponse $monitoringResponse): ?float
    {
        if ($monitoring->type !== MonitoringType::SERVER_HEALTH) {
            return $monitoringResponse->response_time;
        }

        $serviceChecks = $monitoringResponse->server_health_metrics['service_checks'] ?? [];

        if (! is_array($serviceChecks)) {
            return null;
        }

        $responseTimes = collect($serviceChecks)
            ->filter(static fn (mixed $serviceCheck): bool => is_array($serviceCheck) && ($serviceCheck['success'] ?? false) === true)
            ->pluck('response_time_ms')
            ->filter(static fn (mixed $responseTime): bool => is_numeric($responseTime));

        return $responseTimes->isEmpty() ? null : (float) $responseTimes->max();
    }
}
