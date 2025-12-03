<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use App\Models\MonitoringSslResult;
use Illuminate\Console\Command;

class DeleteSoftDeleteMonitorings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:delete-soft-deleted';

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

        $monitorings = Monitoring::onlyTrashed()->get();

        if ($monitorings->isEmpty()) {
            $this->info('No soft-deleted monitorings found.');

            return Command::SUCCESS;
        }

        foreach ($monitorings as $monitoring) {
            $this->comment("Deleting data for monitoring: {$monitoring->name} (ID: {$monitoring->id})");

            // Delete related response results
            MonitoringResponse::query()->where('monitoring_id', $monitoring->id)->forceDelete();

            // Delete related daily results
            MonitoringDailyResult::query()->where('monitoring_id', $monitoring->id)->forceDelete();

            // Delete related archived response results
            MonitoringResponseArchived::query()->where('monitoring_id', $monitoring->id)->forceDelete();

            // Delete related SSL results
            MonitoringSslResult::query()->where('monitoring_id', $monitoring->id)->forceDelete();

            // Delete related incidents
            Incident::query()->where('monitoring_id', $monitoring->id)->forceDelete();

            // Delete related notifications
            MonitoringNotification::query()->where('monitoring_id', $monitoring->id)->forceDelete();

            // Force delete the monitoring itself
            $monitoring->forceDelete();

            $this->info("Successfully deleted monitoring: {$monitoring->name} (ID: {$monitoring->id}) and its related data.");
        }

        $this->info('Soft-deleted monitorings and their related data deleted successfully.');

        return Command::SUCCESS;
    }
}
