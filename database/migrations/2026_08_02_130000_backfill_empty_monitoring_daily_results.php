<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
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
            $sources = DB::table('monitoring_daily_results as source')
                ->join('monitorings', 'monitorings.id', '=', 'source.monitoring_id')
                ->whereDate('source.date', $sourceDate)
                ->where('monitorings.created_at', '<', '2026-06-18')
                ->whereRaw('(source.uptime_minutes + source.downtime_minutes) > 0')
                ->select([
                    'source.monitoring_id',
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
                ->get();

            foreach ($sources as $source) {
                $target = DB::table('monitoring_daily_results')
                    ->where('monitoring_id', $source->monitoring_id)
                    ->whereDate('date', $targetDate)
                    ->whereRaw('(uptime_minutes + downtime_minutes) = 0')
                    ->first(['id']);

                if ($target === null) {
                    continue;
                }

                DB::table('monitoring_daily_results')
                    ->where('id', $target->id)
                    ->update([
                        'uptime_total' => $source->uptime_total,
                        'downtime_total' => $source->downtime_total,
                        'unknown_total' => $source->unknown_total,
                        'uptime_percentage' => $source->uptime_percentage,
                        'downtime_percentage' => $source->downtime_percentage,
                        'unknown_percentage' => $source->unknown_percentage,
                        'uptime_minutes' => $source->uptime_minutes,
                        'downtime_minutes' => $source->downtime_minutes,
                        'unknown_minutes' => $source->unknown_minutes,
                        'avg_response_time' => $source->avg_response_time,
                        'min_response_time' => $source->min_response_time,
                        'max_response_time' => $source->max_response_time,
                        'incidents_count' => $source->incidents_count,
                        'updated_at' => $timestamp,
                    ]);
            }
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
};
