<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\MonitoringDetailQuery;
use App\Services\MobileMonitoringDetailPayloadService;
use App\Services\MonitoringCheckHistoryService;
use App\Services\MonitoringServerHealthTelemetryService;
use App\Support\MonitoringDateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MonitoringDetailDataController extends Controller
{
    private const DAYS = 30;

    private const RECENT_CHECKS_LIMIT = 10;

    private const INCIDENT_LIMIT = 10;

    public function __invoke(
        Request $request,
        string $monitoring,
        MonitoringDetailQuery $monitoringDetailQuery,
        MobileMonitoringDetailPayloadService $mobileMonitoringDetailPayloadService,
        MonitoringCheckHistoryService $monitoringCheckHistoryService,
        MonitoringServerHealthTelemetryService $monitoringServerHealthTelemetryService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $monitoring = $monitoringDetailQuery->findVisible($user, $monitoring);
        $range = MonitoringDateRange::pastDays(self::DAYS);
        $payload = $mobileMonitoringDetailPayloadService->for(
            $monitoring,
            $user,
            self::DAYS,
            self::INCIDENT_LIMIT,
            0,
        );
        $recentChecks = $monitoringCheckHistoryService->getHistory(
            $monitoring,
            $range->startDate,
            $range->endDate,
            self::RECENT_CHECKS_LIMIT,
            0,
        );

        $payload['data']['recent_checks'] = $recentChecks['data'];
        $payload['meta']['recent_checks'] = [
            'limit' => self::RECENT_CHECKS_LIMIT,
            'has_more' => $recentChecks['has_more'],
            'next_offset' => $recentChecks['next_offset'],
        ];
        $payload['data']['server_health_telemetry'] = $monitoring->isServerHealth()
            ? $monitoringServerHealthTelemetryService->getTelemetry($monitoring, $range->startDate, $range->endDate)
            : null;

        return response()->json($payload);
    }
}
