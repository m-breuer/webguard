<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Services\MonitoringAvailabilityService;
use App\Services\MonitoringIncidentService;
use App\Services\MonitoringResponseTimeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

#[Description('Aggregates daily monitoring results.')]
#[Signature('monitoring:aggregate-daily {days=1 : The number of past days to aggregate (default: 1)}')]
class AggregateDailyResultsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(
        MonitoringAvailabilityService $monitoringAvailabilityService,
        MonitoringResponseTimeService $monitoringResponseTimeService,
        MonitoringIncidentService $monitoringIncidentService
    ) {
        $this->info('Starting daily monitoring results aggregation and cleanup...');

        $daysToAggregate = (int) $this->argument('days');

        for ($i = 1; $i <= $daysToAggregate; $i++) {
            $date = now()->subDays($i)->startOfDay()->copy(); // Aggregate for past days

            $this->info('Aggregating for date: ' . $date->toDateString());

            // Get all monitorings that have responses on this specific date
            $monitoringsWithResponses = Monitoring::query()->whereHas('responseResults', function (Builder $builder) use ($date) {
                $builder->whereDate('created_at', $date->toDateString());
            })->get();

            foreach ($monitoringsWithResponses as $monitoringWithResponse) {
                $this->info('  Aggregating for monitoring: ' . $monitoringWithResponse->name . ' (' . $monitoringWithResponse->id . ')');

                $uptimeDowntime = $monitoringAvailabilityService->getUptimeDowntime($monitoringWithResponse, $date->copy()->startOfDay(), $date->copy()->endOfDay());
                $responseTimes = $monitoringResponseTimeService->getResponseTimes($monitoringWithResponse, $date->copy()->startOfDay(), $date->copy()->endOfDay());
                $incidents = $monitoringIncidentService->getIncidents($monitoringWithResponse, $date->copy()->startOfDay(), $date->copy()->endOfDay());

                MonitoringDailyResult::query()->updateOrCreate([
                    'monitoring_id' => $monitoringWithResponse->id,
                    'date' => $date->toDateString(),
                ], [
                    'uptime_total' => $uptimeDowntime->uptime->total,
                    'downtime_total' => $uptimeDowntime->downtime->total,
                    'unknown_total' => $uptimeDowntime->unknown->total,
                    'uptime_percentage' => $uptimeDowntime->uptime->percentage ?? 0.0,
                    'downtime_percentage' => $uptimeDowntime->downtime->percentage ?? 0.0,
                    'unknown_percentage' => $uptimeDowntime->unknown->percentage ?? 0.0,
                    'uptime_minutes' => $uptimeDowntime->uptime->minutes,
                    'downtime_minutes' => $uptimeDowntime->downtime->minutes,
                    'unknown_minutes' => $uptimeDowntime->unknown->minutes,
                    'avg_response_time' => $responseTimes->aggregated->avg ?? 0,
                    'min_response_time' => $responseTimes->aggregated->min ?? 0,
                    'max_response_time' => $responseTimes->aggregated->max ?? 0,
                    'incidents_count' => $incidents->count(),
                ]);

                $this->info(sprintf(
                    '    -> Uptime: %.2f%% (%d min, Total: %d) | Downtime: %.2f%% (%d min, Total: %d) | Unknown: %.2f%% (%d min, Total: %d) | Avg RT: %.2f ms (Min: %.2f, Max: %.2f) | Incidents: %d',
                    $uptimeDowntime->uptime->percentage ?? 0.0,
                    $uptimeDowntime->uptime->minutes,
                    $uptimeDowntime->uptime->total,
                    $uptimeDowntime->downtime->percentage ?? 0.0,
                    $uptimeDowntime->downtime->minutes,
                    $uptimeDowntime->downtime->total,
                    $uptimeDowntime->unknown->percentage ?? 0.0,
                    $uptimeDowntime->unknown->minutes,
                    $uptimeDowntime->unknown->total,
                    $responseTimes->aggregated->avg ?? 0,
                    $responseTimes->aggregated->min ?? 0,
                    $responseTimes->aggregated->max ?? 0,
                    $incidents->count()
                ));
            }
        }

        $this->info('Daily aggregation and cleanup completed.');

        return Command::SUCCESS;
    }
}
