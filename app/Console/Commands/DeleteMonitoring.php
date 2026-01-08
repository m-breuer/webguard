<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Monitoring;
use Illuminate\Console\Command;

class DeleteMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:delete {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a monitoring by ID, including all its related data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitoringId = $this->argument('id');

        if (! $monitoringId) {
            $monitoringId = $this->ask('Enter the Monitoring ID to delete');
        }

        $monitoring = Monitoring::withTrashed()->find($monitoringId);

        if (! $monitoring) {
            $this->error("Monitoring with ID '{$monitoringId}' not found.");

            return Command::FAILURE;
        }

        if (! $this->confirm("Are you sure you want to soft delete monitoring '{$monitoring->name}' (ID: {$monitoring->id})? It will be hidden from views and APIs.")) {
            $this->info('Deletion cancelled.');

            return Command::SUCCESS;
        }

        $this->comment("Soft deleting monitoring: {$monitoring->name} (ID: {$monitoring->id})");

        // Soft delete the monitoring itself
        $monitoring->delete();

        $this->info("Successfully soft deleted monitoring: {$monitoring->name} (ID: {$monitoring->id}). Related data will be permanently deleted by the monthly cleanup command.");

        return Command::SUCCESS;
    }
}
