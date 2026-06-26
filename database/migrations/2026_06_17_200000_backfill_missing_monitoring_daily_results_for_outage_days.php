<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $targetDates = $this->targetDates();
        $timestamp = now()->toDateTimeString();

        DB::table('monitoring_daily_results')->insertUsing(
            [
                'monitoring_id',
                'date',
                'uptime_total',
                'downtime_total',
                'unknown_total',
                'uptime_percentage',
                'downtime_percentage',
                'unknown_percentage',
                'uptime_minutes',
                'downtime_minutes',
                'unknown_minutes',
                'avg_response_time',
                'min_response_time',
                'max_response_time',
                'incidents_count',
                'created_at',
                'updated_at',
            ],
            DB::query()
                ->fromSub($targetDates, 'target_dates')
                ->join('monitoring_daily_results as source', 'source.date', '=', 'target_dates.source_date')
                ->leftJoin('monitoring_daily_results as existing', function (JoinClause $join): void {
                    $join
                        ->on('existing.monitoring_id', '=', 'source.monitoring_id')
                        ->on('existing.date', '=', 'target_dates.missing_date');
                })
                ->whereNull('existing.id')
                ->select([
                    'source.monitoring_id',
                    'target_dates.missing_date',
                    'source.uptime_total',
                    'source.downtime_total',
                    'source.unknown_total',
                    'source.uptime_percentage',
                    'source.downtime_percentage',
                    'source.unknown_percentage',
                    'source.uptime_minutes',
                    'source.downtime_minutes',
                    'source.unknown_minutes',
                    'source.avg_response_time',
                    'source.min_response_time',
                    'source.max_response_time',
                    'source.incidents_count',
                ])
                ->selectRaw('? as created_at, ? as updated_at', [$timestamp, $timestamp])
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function targetDates(): Builder
    {
        $datePairs = [
            ['2025-10-19', '2025-10-18'],
            ['2025-12-16', '2025-12-15'],
            ['2026-01-07', '2026-01-06'],
            ['2026-04-28', '2026-04-27'],
            ['2026-06-05', '2026-06-04'],
            ['2026-06-16', '2026-06-15'],
        ];

        $targetDates = null;

        foreach ($datePairs as [$missingDate, $sourceDate]) {
            $query = DB::query()->selectRaw('? as missing_date, ? as source_date', [$missingDate, $sourceDate]);

            $targetDates = $targetDates instanceof Builder
                ? $targetDates->unionAll($query)
                : $query;
        }

        return $targetDates;
    }
};
