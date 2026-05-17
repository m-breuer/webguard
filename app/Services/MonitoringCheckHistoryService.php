<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitoring;
use App\Support\MonitoringStatusMeta;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class MonitoringCheckHistoryService
{
    private const LIVE_TABLE = 'monitoring_response_results';

    private const ARCHIVED_TABLE = 'monitoring_response_archived';

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
        $archiveCutoffDate = Date::now()->subWeek()->startOfDay();

        if ($startDate !== null && $startDate->gte($archiveCutoffDate)) {
            $rows = $this->buildSourceQuery(
                self::LIVE_TABLE,
                'live',
                $monitoring->id,
                $startDate,
                $endDate,
                $offset,
                $pageSize
            )->get();

            return $this->paginateRows($rows, $limit, $offset);
        }

        if ($startDate === null) {
            $liveRows = $this->buildSourceQuery(
                self::LIVE_TABLE,
                'live',
                $monitoring->id,
                null,
                null,
                $offset,
                $pageSize
            )->get();

            $oldestLiveCheckedAt = $liveRows->last()?->created_at;

            if (
                $liveRows->count() === $pageSize
                && $oldestLiveCheckedAt !== null
                && Date::parse((string) $oldestLiveCheckedAt)->gte($archiveCutoffDate)
            ) {
                return $this->paginateRows($liveRows, $limit, $offset);
            }
        }

        $rows = $this->buildUnionQuery(
            $monitoring->id,
            $startDate,
            $startDate !== null ? $endDate : null
        )
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        return $this->paginateRows($rows, $limit, $offset);
    }

    private function buildSourceQuery(
        string $table,
        string $source,
        string $monitoringId,
        ?Carbon $startDate,
        ?Carbon $endDate,
        int $offset,
        int $limit
    ): QueryBuilder {
        return $this->buildSourceSubquery($table, $source, $monitoringId, $startDate, $endDate)->latest()
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit);
    }

    private function buildUnionQuery(string $monitoringId, ?Carbon $startDate, ?Carbon $endDate): QueryBuilder
    {
        $builder = $this->buildSourceSubquery(
            self::LIVE_TABLE,
            'live',
            $monitoringId,
            $startDate,
            $endDate
        );
        $archivedQuery = $this->buildSourceSubquery(
            self::ARCHIVED_TABLE,
            'archived',
            $monitoringId,
            $startDate,
            $endDate
        );

        return DB::query()
            ->fromSub($builder->unionAll($archivedQuery), 'monitoring_results')->latest()
            ->orderByDesc('id');
    }

    private function buildSourceSubquery(
        string $table,
        string $source,
        string $monitoringId,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): QueryBuilder {
        $builder = DB::table($table)
            ->selectRaw("'{$source}' as source, id, status, http_status_code, response_time, server_health_metrics, created_at")
            ->where('monitoring_id', $monitoringId);

        if ($startDate !== null && $endDate !== null) {
            $builder->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $builder;
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
