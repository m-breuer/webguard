<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\MonitoringAvailabilityPayload;
use App\Data\MonitoringResponseTimesPayload;
use App\Http\Requests\Api\MonitoringChecksRequest;
use App\Http\Requests\Api\MonitoringDashboardRequest;
use App\Http\Requests\Api\MonitoringDaysRequest;
use App\Http\Requests\Api\MonitoringUptimeCalendarRequest;
use App\Http\Requests\Api\MonitoringUptimeSummaryRequest;
use App\Models\Monitoring;
use App\Models\User;
use App\Queries\MonitoringDataQuery;
use App\Services\MonitoringAvailabilityService;
use App\Services\MonitoringBadgePayloadService;
use App\Services\MonitoringCheckHistoryService;
use App\Services\MonitoringDashboardPayloadService;
use App\Services\MonitoringHeatmapService;
use App\Services\MonitoringIncidentService;
use App\Services\MonitoringResponseTimeService;
use App\Services\MonitoringStatsCache;
use App\Services\MonitoringStatusPayloadService;
use App\Services\MonitoringUptimeCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * @group Monitoring API
 *
 * This controller is responsible for handling all API requests related to monitoring data.
 * It provides endpoints for retrieving uptime/downtime, response times, incidents, and other monitoring statistics.
 * The controller makes extensive use of caching to ensure optimal performance.
 */
class ApiController extends Controller
{
    public function __construct(
        private readonly MonitoringStatsCache $monitoringStatsCache,
        private readonly MonitoringDataQuery $monitoringDataQuery,
    ) {}

    /**
     * Retrieves all data for a given monitoring instance.
     *
     * @response {
     *  "status_since": {
     *  "status": "UP",
     *  "time": "2021-01-01 00:00:00"
     *  },
     *  "status_now": {
     *  "status": "UP"
     *  },
     *  "uptime_downtime": [
     *  {
     *  "date": "2021-01-01",
     *  "uptime": 100,
     *  "downtime": 0
     *  }
     *  ],
     *  "response_times": [
     *  {
     *  "datetime": "2021-01-01 00:00:00",
     *  "response_time": 123
     *  }
     *  ],
     *  "incidents": [
     *  {
     *  "started_at": "2021-01-01 00:00:00",
     *  "finished_at": "2021-01-01 00:05:00",
     *  "type": "DOWN",
     *  "reason": "HTTP status code 500"
     *  }
     *  ],
     *  "heatmap": [
     *  {
     *  "hour": "00:00",
     *  "uptime": 100
     *  }
     *  ],
     *  "ssl": {
     *  "valid": true,
     *  "expiration": "2022-01-01T00:00:00.000000Z",
     *  "issuer": "Let's Encrypt",
     *  "issue_date": "2021-10-01T00:00:00.000000Z"
     *  },
     *  "uptime_calendar": {
     *  "2021-01": [
     *  {
     *  "date": "2021-01-01",
     *  "uptime": "100.00"
     *  }
     *  ]
     *  }
     * }
     */
    public function all(
        Monitoring $monitoring,
        MonitoringDashboardRequest $monitoringDashboardRequest,
        MonitoringDashboardPayloadService $monitoringDashboardPayloadService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $days = $monitoringDashboardRequest->days();
        $calendarStartDate = $monitoringDashboardRequest->calendarStartDate();
        $calendarEndDate = $monitoringDashboardRequest->calendarEndDate();
        $monitoringDateRange = $monitoringDashboardRequest->dateRange();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->dashboardKey($monitoring, $days, $monitoringDateRange, $calendarStartDate, $calendarEndDate),
            fn (): array => $monitoringDashboardPayloadService->getPayload(
                $monitoring,
                $days,
                $calendarStartDate,
                $calendarEndDate
            )->toArray()
        );

        return response()->json($data);
    }

    /**
     * Retrieves the uptime and downtime data for a given monitoring instance.
     *
     * @queryParam days integer The number of days to retrieve data for. Defaults to 30. Example: 30
     *
     * @response [
     * {
     * "date": "2021-01-01",
     * "uptime": 100,
     * "downtime": 0
     * }
     * ]
     */
    public function uptimeDowntime(
        Monitoring $monitoring,
        MonitoringDaysRequest $monitoringDaysRequest,
        MonitoringAvailabilityService $monitoringAvailabilityService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $days = $monitoringDaysRequest->days();
        $monitoringDateRange = $monitoringDaysRequest->dateRange();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->uptimeKey($monitoring, $days, $monitoringDateRange),
            fn (): MonitoringAvailabilityPayload => $monitoringAvailabilityService->getUptimeDowntime(
                $monitoring,
                $monitoringDateRange->startDate,
                $monitoringDateRange->endDate,
                $monitoringDateRange->shouldUseUptimeAggregates($monitoring),
                $monitoringDateRange->shouldIncludeIntradayRawData()
            )
        );

        return response()->json($data);
    }

    /**
     * Retrieves uptime and downtime data for multiple day ranges in one request.
     *
     * @queryParam days[] integer[] The day ranges to retrieve. Example: [7, 30, 90]
     *
     * @response {
     *   "data": {
     *     "7": {
     *       "has_data": true
     *     },
     *     "30": {
     *       "has_data": true
     *     }
     *   }
     * }
     */
    public function uptimeDowntimeSummary(
        Monitoring $monitoring,
        MonitoringUptimeSummaryRequest $monitoringUptimeSummaryRequest,
        MonitoringAvailabilityService $monitoringAvailabilityService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $days = $monitoringUptimeSummaryRequest->days();

        $endDate = now()->endOfDay();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->uptimeSummaryKey($monitoring, $days, $endDate),
            fn (): array => $monitoringAvailabilityService->getUptimeDowntimesForRanges($monitoring, $days->all())
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Retrieves the response times for a given monitoring instance.
     *
     * @queryParam days integer The number of days to retrieve data for. Defaults to 30. Example: 30
     *
     * @response [
     * {
     * "datetime": "2021-01-01 00:00:00",
     * "response_time": 123
     * }
     * ]
     */
    public function responseTimes(
        Monitoring $monitoring,
        MonitoringDaysRequest $monitoringDaysRequest,
        MonitoringResponseTimeService $monitoringResponseTimeService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $days = $monitoringDaysRequest->days();
        $monitoringDateRange = $monitoringDaysRequest->dateRange();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->responseTimesKey($monitoring, $days, $monitoringDateRange),
            fn (): MonitoringResponseTimesPayload => $monitoringResponseTimeService->getResponseTimes(
                $monitoring,
                $monitoringDateRange->startDate,
                $monitoringDateRange->endDate,
                $monitoringDateRange->shouldUseResponseTimeAggregates()
            )
        );

        return response()->json($data);
    }

    /**
     * Retrieves historical monitoring checks including status code details.
     *
     * @queryParam days integer Optional number of past days to include. If omitted, all available history is considered.
     * @queryParam limit integer Optional maximum number of entries returned. Defaults to 100.
     * @queryParam offset integer Optional number of entries to skip for pagination. Defaults to 0.
     *
     * @response {
     *   "data": [
     *     {
     *       "id": "01H...",
     *       "checked_at": "2026-03-24T12:00:00Z",
     *       "status": "down",
     *       "http_status_code": 503,
     *       "response_time": 210.5,
     *       "status_identifier": "status.server_error",
     *       "status_key": "notifications.status.server_error",
     *       "source": "live"
     *     }
     *   ],
     *   "meta": {
     *     "count": 1,
     *     "limit": 100,
     *     "offset": 0,
     *     "days": 7,
     *     "has_more": false,
     *     "next_offset": null
     *   }
     * }
     */
    public function checks(
        Monitoring $monitoring,
        MonitoringChecksRequest $monitoringChecksRequest,
        MonitoringCheckHistoryService $monitoringCheckHistoryService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $days = $monitoringChecksRequest->days();
        $limit = $monitoringChecksRequest->limit();
        $offset = $monitoringChecksRequest->offset();
        $startDate = $monitoringChecksRequest->startDate();
        $endDate = $monitoringChecksRequest->endDate();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->checksKey($monitoring, $days, $limit, $offset),
            fn (): array => $monitoringCheckHistoryService->getHistory($monitoring, $startDate, $endDate, $limit, $offset)
        );

        return response()->json([
            'data' => $data['data'],
            'meta' => [
                'count' => count($data['data']),
                'limit' => $limit,
                'offset' => $offset,
                'days' => $days,
                'has_more' => $data['has_more'],
                'next_offset' => $data['next_offset'],
            ],
        ]);
    }

    /**
     * Retrieves the uptime heatmap data for a given monitoring instance.
     *
     * @response [
     * {
     * "hour": "00:00",
     * "uptime": 100
     * }
     * ]
     */
    public function uptimeHeatmap(
        Monitoring $monitoring,
        MonitoringHeatmapService $monitoringHeatmapService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $start_date = now()->subHours(23);
        $end_date = now();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->heatmapKey($monitoring),
            fn (): Collection => $monitoringHeatmapService->getHeatmap($monitoring, $start_date, $end_date),
            $this->monitoringStatsCache->heatmapExpiresAt()
        );

        return response()->json($data);
    }

    /**
     * Retrieves the combined status of a given monitoring instance.
     *
     * @response {
     * "status": "UP",
     * "since": "2021-01-01 00:00:00",
     * "checked_at": "2021-01-01 00:00:00",
     * "next": "2021-01-01 00:05:00",
     * "interval": 300
     * }
     */
    public function status(
        Monitoring $monitoring,
        MonitoringStatusPayloadService $monitoringStatusPayloadService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        return response()->json($monitoringStatusPayloadService->getPayload($monitoring)->toArray());
    }

    /**
     * Retrieves the public SLA badge payload for a monitoring instance.
     *
     * @response {
     *   "name": "Primary API",
     *   "status": "up",
     *   "status_label": "UP",
     *   "status_code": 200,
     *   "status_identifier": "status.success",
     *   "status_key": "notifications.status.success",
     *   "checked_at": "2026-04-12T10:00:00Z",
     *   "checked_at_human": "04/12/2026 10:00 AM",
     *   "uptime": {
     *     "7_days": 100,
     *     "30_days": 99.9,
     *     "90_days": 99.5,
     *     "365_days": 99.1
     *   },
     *   "public_url": "https://example.com/label/01H...",
     *   "incidents": {
     *     "30_days": 0,
     *     "90_days": 1,
     *     "365_days": 2
     *   },
     *   "ssl": {
     *     "valid": true,
     *     "expires_at": "2026-08-01T00:00:00+00:00"
     *   },
     *   "domain": {
     *     "valid": true,
     *     "expires_at": "2027-02-01T00:00:00+00:00"
     *   },
     *   "maintenance": {
     *     "active": false,
     *     "starts_at": null,
     *     "ends_at": null
     *   }
     * }
     */
    public function badge(
        Monitoring $monitoring,
        MonitoringBadgePayloadService $monitoringBadgePayloadService
    ): JsonResponse {
        abort_unless($monitoring->public_label_enabled, 404);

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->badgeKey($monitoring),
            fn (): array => $monitoringBadgePayloadService->getPayload($monitoring)->toArray()
        );

        return response()->json($data);
    }

    /**
     * Retrieves the incidents for a given monitoring instance.
     *
     * @queryParam days integer The number of days to retrieve data for. Defaults to 30. Example: 30
     *
     * @response [
     * {
     * "started_at": "2021-01-01 00:00:00",
     * "finished_at": "2021-01-01 00:05:00",
     * "type": "DOWN",
     * "reason": "HTTP status code 500"
     * }
     * ]
     */
    public function incidents(
        Monitoring $monitoring,
        MonitoringDaysRequest $monitoringDaysRequest,
        MonitoringIncidentService $monitoringIncidentService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $days = $monitoringDaysRequest->days();
        $monitoringDateRange = $monitoringDaysRequest->dateRange();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->incidentsKey($monitoring, $days, $monitoringDateRange),
            fn (): Collection => $monitoringIncidentService->getIncidents($monitoring, $monitoringDateRange->startDate, $monitoringDateRange->endDate),
        );

        return response()->json($data);
    }

    /**
     * Retrieves the SSL status for a given monitoring instance.
     *
     * @response {
     * "valid": true,
     * "expiration": "2022-01-01T00:00:00.000000Z",
     * "issuer": "Let's Encrypt",
     * "issue_date": "2021-10-01T00:00:00.000000Z"
     * }
     */
    public function sslStatus(
        Monitoring $monitoring,
        MonitoringDashboardPayloadService $monitoringDashboardPayloadService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->sslStatusKey($monitoring),
            fn (): array => $monitoringDashboardPayloadService->getSslPayload($monitoring)->toArray()
        );

        return response()->json($data);
    }

    /**
     * Retrieves the uptime calendar data for a given monitoring instance.
     *
     * @queryParam start_date date required The start date to retrieve data for. Example: 2021-01-01
     * @queryParam end_date date required The end date to retrieve data for. Example: 2021-01-31
     *
     * @response {
     * "2021-01": [
     * {
     * "date": "2021-01-01",
     * "uptime": "100.00"
     * }
     * ]
     * }
     */
    public function uptimeCalendar(
        Monitoring $monitoring,
        MonitoringUptimeCalendarRequest $monitoringUptimeCalendarRequest,
        MonitoringUptimeCalendarService $monitoringUptimeCalendarService
    ): JsonResponse {
        $monitoring = $this->accessibleMonitoring($monitoring);

        $startDate = $monitoringUptimeCalendarRequest->startDate();
        $endDate = $monitoringUptimeCalendarRequest->endDate();

        $data = $this->monitoringStatsCache->remember(
            $monitoring,
            $this->monitoringStatsCache->uptimeCalendarKey($monitoring, $startDate, $endDate),
            fn (): array => $monitoringUptimeCalendarService->getGroupedByDateAndMonth($monitoring, $startDate, $endDate)->toArray(),
            $this->monitoringStatsCache->calendarTtlSeconds()
        );

        return response()->json($data);
    }

    private function accessibleMonitoring(Monitoring $monitoring): Monitoring
    {
        /** @var User|null $user */
        $user = request()->user();

        return $this->monitoringDataQuery->findAccessible($user, (string) $monitoring->getKey());
    }
}
