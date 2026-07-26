<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitoring;
use App\Queries\MonitoringCheckHistoryQuery;
use App\Support\MonitoringStatusMeta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringCheckHistoryService
{
    public function __construct(private readonly MonitoringCheckHistoryQuery $monitoringCheckHistoryQuery) {}

    /**
     * @return array{data: array<int, array{id: string, checked_at: string, status: string, http_status_code: int|null, response_time: float|null, server_health_metrics: array<string, mixed>|null, status_identifier: string, status_key: string, source: string}>, has_more: bool, next_offset: int|null}
     */
    public function getHistory(
        Monitoring $monitoring,
        ?Carbon $startDate,
        Carbon $endDate,
        int $limit,
        int $offset
    ): array {
        $pageSize = $limit + 1;
        $rows = $this->monitoringCheckHistoryQuery->for(
            (string) $monitoring->getKey(),
            $startDate,
            $endDate,
            $offset,
            $pageSize,
        );

        return $this->paginateRows($rows, $limit, $offset);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array{id: string, checked_at: string, status: string, http_status_code: int|null, response_time: float|null, server_health_metrics: array<string, mixed>|null, status_identifier: string, status_key: string, source: string}>
     */
    private function formatRows(Collection $rows): array
    {
        return $rows->map(function (object $row): array {
            $httpStatusCode = $row->http_status_code !== null ? (int) $row->http_status_code : null;
            $serverHealthMetrics = null;

            if (isset($row->server_health_metrics)) {
                $decodedMetrics = json_decode((string) $row->server_health_metrics, true);
                $serverHealthMetrics = is_array($decodedMetrics) ? $decodedMetrics : null;
            }

            return [
                'id' => (string) $row->id,
                'checked_at' => Date::parse((string) $row->created_at)->toIso8601String(),
                'status' => (string) $row->status,
                'http_status_code' => $httpStatusCode,
                'response_time' => $row->response_time !== null ? (float) $row->response_time : null,
                'server_health_metrics' => $serverHealthMetrics,
                'status_identifier' => MonitoringStatusMeta::statusIdentifier($httpStatusCode),
                'status_key' => MonitoringStatusMeta::statusKey($httpStatusCode),
                'source' => (string) $row->source,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{data: array<int, array{id: string, checked_at: string, status: string, http_status_code: int|null, response_time: float|null, server_health_metrics: array<string, mixed>|null, status_identifier: string, status_key: string, source: string}>, has_more: bool, next_offset: int|null}
     */
    private function paginateRows(Collection $rows, int $limit, int $offset): array
    {
        $hasMore = $rows->count() > $limit;
        $pageRows = $rows->take($limit);

        return [
            'data' => $this->formatRows($pageRows),
            'has_more' => $hasMore,
            'next_offset' => $hasMore ? $offset + $pageRows->count() : null,
        ];
    }
}
