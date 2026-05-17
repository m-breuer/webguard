<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringStatusPayload;
use App\Data\MonitoringSummaryPayload;
use App\Models\Monitoring;
use App\Support\MonitoringStatusMeta;

class MonitoringStatusPayloadService
{
    public function __construct(
        private readonly MonitoringStatusService $monitoringStatusService
    ) {}

    public function getPayload(Monitoring $monitoring, bool $includeMonitoring = true): MonitoringStatusPayload
    {
        $statusSince = $this->monitoringStatusService->getStatusSince($monitoring);
        $statusNow = $this->monitoringStatusService->getStatusNow($monitoring);
        $latestStatusCode = $monitoring->latestResponseResult?->http_status_code;
        $maintenanceActive = $monitoring->isUnderMaintenance();

        return new MonitoringStatusPayload(
            status: $statusNow['status'],
            since: $statusSince['since'] ?? null,
            checkedAt: $statusNow['checked_at'],
            next: $statusNow['next'],
            interval: $statusNow['interval'],
            statusCode: $latestStatusCode,
            statusChangedAt: $statusSince['since'] ?? null,
            statusIdentifier: MonitoringStatusMeta::statusIdentifier($latestStatusCode, $maintenanceActive),
            statusKey: MonitoringStatusMeta::statusKey($latestStatusCode, $maintenanceActive),
            monitoring: $includeMonitoring
                ? new MonitoringSummaryPayload(
                    name: $monitoring->name,
                    target: $monitoring->target,
                    type: $monitoring->type->value
                )
                : null
        );
    }
}
