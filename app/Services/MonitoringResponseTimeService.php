<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringResponseTimeAggregatePayload;
use App\Data\MonitoringResponseTimePointPayload;
use App\Data\MonitoringResponseTimesPayload;
use App\Models\Monitoring;
use App\Support\MonitoringResponseHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringResponseTimeService
{
    public function getResponseTimes(Monitoring $monitoring, Carbon $startDate, Carbon $endDate, bool $loadAggregatedData = false): MonitoringResponseTimesPayload
    {
        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->endOfDay();

        if ($loadAggregatedData) {
            return $this->getAggregatedResponseTimes($monitoring, $startDate, $endDate);
        }

        return $this->getRawResponseTimes($monitoring, $startDate, $endDate);
    }

    private function getAggregatedResponseTimes(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): MonitoringResponseTimesPayload
    {
        $dailyAggregatedData = $monitoring->dailyResults()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        $combinedData = $dailyAggregatedData->map(fn ($row): MonitoringResponseTimePointPayload => new MonitoringResponseTimePointPayload(
            date: Date::parse($row->date)->toIso8601String(),
            avg: $row->avg_response_time ?? 0,
            min: $row->min_response_time ?? 0,
            max: $row->max_response_time ?? 0
        ));

        return $this->buildResponse($combinedData);
    }

    private function getRawResponseTimes(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): MonitoringResponseTimesPayload
    {
        $grouping = MonitoringResponseHistory::groupingForDays((int) Date::parse($startDate)->diffInDays($endDate));
        $periodExpression = MonitoringResponseHistory::periodExpression('created_at', $grouping);

        $data = MonitoringResponseHistory::queryForEndDate($endDate)
            ->where('monitoring_id', $monitoring->id)
            ->selectRaw("{$periodExpression} as period,
                    AVG(response_time) as avg_response_time,
                    MIN(response_time) as min_response_time,
                    MAX(response_time) as max_response_time
                ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('response_time')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $combinedData = $data->map(fn ($row): MonitoringResponseTimePointPayload => new MonitoringResponseTimePointPayload(
            date: Date::parse($row['period'] . ':00:00')->toIso8601String(),
            avg: $row['avg_response_time'],
            min: $row['min_response_time'],
            max: $row['max_response_time']
        ));

        return $this->buildResponse($combinedData);
    }

    /**
     * @param  Collection<int, MonitoringResponseTimePointPayload>  $combinedData
     */
    private function buildResponse(Collection $combinedData): MonitoringResponseTimesPayload
    {
        return new MonitoringResponseTimesPayload(
            data: $combinedData,
            aggregated: new MonitoringResponseTimeAggregatePayload(
                avg: $combinedData->avg('avg'),
                min: $combinedData->min('min'),
                max: $combinedData->max('max')
            )
        );
    }
}
