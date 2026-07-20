<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Services\MonitoringAvailabilityService;
use App\Services\MonitoringIncidentService;
use App\Services\MonitoringStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

/**
 * Class PublicLabelController
 *
 * This controller is responsible for handling requests for public monitoring labels.
 * It retrieves all necessary monitoring data using the ApiController and displays it on a public page.
 */
class PublicLabelController extends Controller
{
    public function __construct(
        private readonly MonitoringStatusService $monitoringStatusService,
        private readonly MonitoringAvailabilityService $monitoringAvailabilityService,
        private readonly MonitoringIncidentService $monitoringIncidentService
    ) {}

    /**
     * Handle the incoming request to display a public monitoring label.
     *
     * @param  Monitoring  $monitoring  The monitoring model
     * @param  Request  $request  The HTTP request instance.
     * @return View The view displaying the public monitoring label.
     */
    public function __invoke(Monitoring $monitoring, Request $request): View
    {
        abort_unless($monitoring->public_label_enabled, 404);

        $monitoring->loadMissing([
            'domainResult',
            'latestIncident',
            'latestResponseResult',
            'sslResult',
        ]);

        $statusSince = $this->monitoringStatusService->getStatusSince($monitoring);
        $statusNow = $this->monitoringStatusService->getStatusNow($monitoring);
        $status = $this->normalizeStatus($statusSince['status'] ?? $statusNow['status'] ?? MonitoringStatus::UNKNOWN->value);
        $rangeSummaries = $this->monitoringAvailabilityService->getUptimeDowntimesForRanges($monitoring, [7, 30, 90]);
        $incidents = $this->monitoringIncidentService->getIncidents(
            $monitoring,
            Date::now()->subDays(90),
            Date::now()
        )->take(10);

        return view('monitorings.public-label', [
            'monitoring' => $monitoring,
            'status' => $status,
            'statusBadgeType' => $this->statusBadgeType($status),
            'statusSince' => $statusSince['since'] ?? null,
            'statusNow' => $statusNow,
            'rangeSummaries' => $rangeSummaries,
            'incidents' => $incidents,
            'displayTarget' => $monitoring->type === MonitoringType::HEARTBEAT ? null : $monitoring->target,
            'isUnderMaintenance' => $monitoring->isUnderMaintenance(),
            'maintenanceWindow' => $monitoring->currentOrUpcomingMaintenanceWindow(),
        ]);
    }

    private function normalizeStatus(mixed $status): string
    {
        if ($status instanceof MonitoringStatus) {
            return $status->value;
        }

        $normalized = mb_strtolower((string) $status);

        return MonitoringStatus::tryFrom($normalized)?->value ?? MonitoringStatus::UNKNOWN->value;
    }

    private function statusBadgeType(string $status): string
    {
        return match ($status) {
            MonitoringStatus::UP->value => 'success',
            MonitoringStatus::DOWN->value => 'danger',
            default => 'warning',
        };
    }
}
