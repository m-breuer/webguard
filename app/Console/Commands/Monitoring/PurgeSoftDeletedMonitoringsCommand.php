<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use App\Models\MonitoringSslResult;
use Illuminate\Console\Command;

class PurgeSoftDeletedMonitoringsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:purge-soft-deleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all soft-deleted monitorings and their related data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Deleting soft-deleted monitorings and their related data...');

        $monitoringIds = Monitoring::onlyTrashed()->pluck('id');

        if ($monitoringIds->isEmpty()) {
            $this->info('No soft-deleted monitorings found.');

            return Command::SUCCESS;
        }

        $this->comment("Found {$monitoringIds->count()} soft-deleted monitorings to purge.");

        // Delete related data in bulk
        MonitoringResponse::query()->whereIn('monitoring_id', $monitoringIds)->forceDelete();
        MonitoringDailyResult::query()->whereIn('monitoring_id', $monitoringIds)->forceDelete();
        MonitoringResponseArchived::query()->whereIn('monitoring_id', $monitoringIds)->forceDelete();
        MonitoringSslResult::query()->whereIn('monitoring_id', $monitoringIds)->forceDelete();
        Incident::query()->whereIn('monitoring_id', $monitoringIds)->forceDelete();
        MonitoringNotification::query()->whereIn('monitoring_id', $monitoringIds)->forceDelete();

        // Force delete the monitorings themselves
        Monitoring::onlyTrashed()->whereIn('id', $monitoringIds)->forceDelete();

        $this->info("Successfully purged {$monitoringIds->count()} soft-deleted monitorings and their related data.");

        return Command::SUCCESS;
    }
}
