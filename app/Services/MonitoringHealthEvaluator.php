<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Support\HttpStatusCodeRanges;

final class MonitoringHealthEvaluator
{
    public function availabilityFor(Monitoring $monitoring, MonitoringResponse $monitoringResponse): MonitoringStatus
    {
        return $this->availabilityFromValues(
            $monitoring,
            $monitoringResponse->status,
            $monitoringResponse->http_status_code,
            $monitoringResponse->server_health_metrics,
            $monitoringResponse->vital_values,
        );
    }

    /**
     * @param  array<string, mixed>|null  $serverHealthMetrics
     * @param  array<string, mixed>|null  $vitalValues
     */
    public function availabilityFromValues(
        Monitoring $monitoring,
        ?MonitoringStatus $monitoringStatus,
        ?int $httpStatusCode,
        ?array $serverHealthMetrics,
        ?array $vitalValues,
    ): MonitoringStatus {
        $vitalValues ??= [];

        return match ($monitoring->type) {
            MonitoringType::HTTP, MonitoringType::KEYWORD => $this->httpAvailability($monitoring, $monitoringStatus, $httpStatusCode, $vitalValues),
            MonitoringType::PING, MonitoringType::PORT => $this->connectionAvailability($monitoringStatus, $vitalValues),
            MonitoringType::DNS_RECORD => $this->dnsAvailability($monitoring, $monitoringStatus, $vitalValues),
            MonitoringType::HEARTBEAT => $this->heartbeatAvailability($monitoringStatus, $vitalValues),
            MonitoringType::SERVER_HEALTH => $this->serverHealthAvailability($monitoring, $monitoringStatus, $serverHealthMetrics),
            default => $monitoringStatus ?? MonitoringStatus::UNKNOWN,
        };
    }

    /** @param array<string, mixed> $vitalValues */
    private function httpAvailability(Monitoring $monitoring, ?MonitoringStatus $monitoringStatus, ?int $httpStatusCode, array $vitalValues): MonitoringStatus
    {
        if (($vitalValues['transport_succeeded'] ?? null) === false) {
            return MonitoringStatus::DOWN;
        }

        if ($httpStatusCode !== null) {
            return HttpStatusCodeRanges::contains($monitoring->expected_http_statuses ?? HttpStatusCodeRanges::DEFAULT, $httpStatusCode)
                ? MonitoringStatus::UP
                : MonitoringStatus::DOWN;
        }

        return $monitoringStatus ?? MonitoringStatus::UNKNOWN;
    }

    /** @param array<string, mixed> $vitalValues */
    private function connectionAvailability(?MonitoringStatus $monitoringStatus, array $vitalValues): MonitoringStatus
    {
        if (is_bool($vitalValues['connection_succeeded'] ?? null)) {
            return $vitalValues['connection_succeeded'] ? MonitoringStatus::UP : MonitoringStatus::DOWN;
        }

        return $monitoringStatus ?? MonitoringStatus::UNKNOWN;
    }

    /** @param array<string, mixed> $vitalValues */
    private function dnsAvailability(Monitoring $monitoring, ?MonitoringStatus $monitoringStatus, array $vitalValues): MonitoringStatus
    {
        $observedValues = $vitalValues['observed_values'] ?? null;

        if (! is_array($observedValues)) {
            return $monitoringStatus ?? MonitoringStatus::UNKNOWN;
        }

        $expectedValues = $monitoring->dns_expected_values ?? [];
        $normalizedObservedValues = array_map(static fn (mixed $value): string => mb_strtolower(mb_trim((string) $value)), $observedValues);

        foreach ($expectedValues as $expectedValue) {
            if (! in_array(mb_strtolower(mb_trim((string) $expectedValue)), $normalizedObservedValues, true)) {
                return MonitoringStatus::DOWN;
            }
        }

        return MonitoringStatus::UP;
    }

    /** @param array<string, mixed> $vitalValues */
    private function heartbeatAvailability(?MonitoringStatus $monitoringStatus, array $vitalValues): MonitoringStatus
    {
        if (($vitalValues['heartbeat_received'] ?? null) === true) {
            return MonitoringStatus::UP;
        }

        if (($vitalValues['heartbeat_overdue'] ?? null) === true) {
            return MonitoringStatus::DOWN;
        }

        return $monitoringStatus ?? MonitoringStatus::UNKNOWN;
    }

    /** @param array<string, mixed>|null $serverHealthMetrics */
    private function serverHealthAvailability(Monitoring $monitoring, ?MonitoringStatus $monitoringStatus, ?array $serverHealthMetrics): MonitoringStatus
    {
        if (! is_array($serverHealthMetrics) || $serverHealthMetrics === []) {
            return $monitoringStatus ?? MonitoringStatus::UNKNOWN;
        }

        foreach ([
            'cpu_usage_percent' => $monitoring->server_health_cpu_threshold_percent,
            'ram_usage_percent' => $monitoring->server_health_ram_threshold_percent,
            'storage_usage_percent' => $monitoring->server_health_storage_threshold_percent,
        ] as $metric => $threshold) {
            if (isset($serverHealthMetrics[$metric]) && is_numeric($serverHealthMetrics[$metric]) && (float) $serverHealthMetrics[$metric] >= (float) ($threshold ?? 90)) {
                return MonitoringStatus::DOWN;
            }
        }

        return MonitoringStatus::UP;
    }
}
