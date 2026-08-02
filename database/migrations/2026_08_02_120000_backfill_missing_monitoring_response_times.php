<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = now();

        foreach ($this->datePairs() as [$sourceDate, $targetDate]) {
            $this->insertMissingDailyResults($sourceDate, $targetDate, $timestamp);
            $this->backfillResponseTimes($sourceDate, $targetDate, $timestamp);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration corrects historical data and cannot be safely reversed.
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function datePairs(): array
    {
        return [
            ['2026-06-18', '2026-06-19'],
            ['2026-07-09', '2026-07-10'],
        ];
    }

    private function backfillResponseTimes(string $sourceDate, string $targetDate, DateTimeInterface $timestamp): void
    {
        $sources = DB::table('monitoring_daily_results as source')
            ->join('monitorings', 'monitorings.id', '=', 'source.monitoring_id')
            ->whereDate('source.date', $sourceDate)
            ->where('monitorings.created_at', '<', '2026-06-18')
            ->where(function ($query): void {
                $query
                    ->whereNotNull('source.avg_response_time')
                    ->orWhereNotNull('source.min_response_time')
                    ->orWhereNotNull('source.max_response_time');
            })
            ->select([
                'source.monitoring_id',
                'source.avg_response_time',
                'source.min_response_time',
                'source.max_response_time',
            ])
            ->get();

        foreach ($sources as $source) {
            $target = DB::table('monitoring_daily_results')
                ->where('monitoring_id', $source->monitoring_id)
                ->whereDate('date', $targetDate)
                ->first([
                    'id',
                    'avg_response_time',
                    'min_response_time',
                    'max_response_time',
                ]);

            if ($target === null) {
                continue;
            }

            $values = [];

            foreach (['avg_response_time', 'min_response_time', 'max_response_time'] as $column) {
                if ($target->{$column} === null && $source->{$column} !== null) {
                    $values[$column] = $source->{$column};
                }
            }

            if ($values === []) {
                continue;
            }

            $values['updated_at'] = $timestamp;

            DB::table('monitoring_daily_results')
                ->where('id', $target->id)
                ->update($values);
        }
    }

    private function insertMissingDailyResults(string $sourceDate, string $targetDate, DateTimeInterface $timestamp): void
    {
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
            DB::table('monitoring_daily_results as source')
                ->join('monitorings', 'monitorings.id', '=', 'source.monitoring_id')
                ->leftJoin('monitoring_daily_results as existing', function (JoinClause $join) use ($targetDate): void {
                    $join
                        ->on('existing.monitoring_id', '=', 'source.monitoring_id')
                        ->whereDate('existing.date', $targetDate);
                })
                ->whereDate('source.date', $sourceDate)
                ->where('monitorings.created_at', '<', '2026-06-18')
                ->whereNull('existing.id')
                ->where(function ($query): void {
                    $query
                        ->whereNotNull('source.avg_response_time')
                        ->orWhereNotNull('source.min_response_time')
                        ->orWhereNotNull('source.max_response_time');
                })
                ->select([
                    'source.monitoring_id',
                ])
                ->selectRaw('? as date', [$targetDate])
                ->addSelect([
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
};
