<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Services\MonitoringResultService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class AggregateDailyMonitoringResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:aggregate-daily-results {days=1 : The number of past days to aggregate (default: 1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates daily monitoring results and cleans up raw data older than one day.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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

                $uptimeDowntime = MonitoringResultService::getUptimeDowntime($monitoringWithResponse, $date->copy()->startOfDay(), $date->copy()->endOfDay());
                $responseTimes = MonitoringResultService::getResponseTimes($monitoringWithResponse, $date->copy()->startOfDay(), $date->copy()->endOfDay());
                $incidents = MonitoringResultService::getIncidents($monitoringWithResponse, $date->copy()->startOfDay(), $date->copy()->endOfDay());

                MonitoringDailyResult::query()->updateOrCreate([
                    'monitoring_id' => $monitoringWithResponse->id,
                    'date' => $date->toDateString(),
                ], [
                    'uptime_total' => $uptimeDowntime['uptime']['total'] ?? 0,
                    'downtime_total' => $uptimeDowntime['downtime']['total'] ?? 0,
                    'uptime_percentage' => $uptimeDowntime['uptime']['percentage'] ?? 0.0,
                    'downtime_percentage' => $uptimeDowntime['downtime']['percentage'] ?? 0.0,
                    'uptime_minutes' => $uptimeDowntime['uptime']['minutes'] ?? 0,
                    'downtime_minutes' => $uptimeDowntime['downtime']['minutes'] ?? 0,
                    'avg_response_time' => $responseTimes['aggregated']['avg'] ?? 0,
                    'min_response_time' => $responseTimes['aggregated']['min'] ?? 0,
                    'max_response_time' => $responseTimes['aggregated']['max'] ?? 0,
                    'incidents_count' => $incidents->count(),
                ]);

                $this->info(sprintf(
                    '    -> Uptime: %.2f%% (%d min, Total: %d) | Downtime: %.2f%% (%d min, Total: %d) | Avg RT: %.2f ms (Min: %.2f, Max: %.2f) | Incidents: %d',
                    $uptimeDowntime['uptime']['percentage'],
                    $uptimeDowntime['uptime']['minutes'],
                    $uptimeDowntime['uptime']['total'], // uptime_total
                    $uptimeDowntime['downtime']['percentage'],
                    $uptimeDowntime['downtime']['minutes'],
                    $uptimeDowntime['downtime']['total'], // downtime_total
                    $responseTimes['aggregated']['avg'],
                    $responseTimes['aggregated']['min'], // min_response_time
                    $responseTimes['aggregated']['max'], // max_response_time
                    $incidents->count()
                ));
            }
        }

        $this->info('Daily aggregation and cleanup completed.');

        return Command::SUCCESS;
    }
}
