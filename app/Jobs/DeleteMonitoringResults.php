<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Monitoring;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class DeleteMonitoringResults
 *
 * This job is responsible for deleting all monitoring results associated with a specific monitoring.
 * It processes results in chunks to avoid memory issues with large datasets.
 */
class DeleteMonitoringResults implements ShouldQueue
{
    use \Illuminate\Foundation\Queue\Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance whose results are to be deleted.
     */
    public function __construct(public Monitoring $monitoring) {}

    /**
     * Execute the job.
     *
     * Deletes monitoring results in chunks to manage memory usage and avoid database locks on large datasets.
     */
    public function handle(): void
    {
        $this->monitoring->responseResults()->delete();

        $this->monitoring->dailyResults()->delete();

        $this->monitoring->archivedResponseResults()->delete();

        $this->monitoring->sslResult()->delete();

        $this->monitoring->incidents()->delete();

        $this->monitoring->notifications()->delete();
    }
}
