<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MonitoringResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ArchiveMonitoringResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:archive-responses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archives monitoring responses for a given date (defaults to yesterday) by moving them to a separate table and deleting them from the live table.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $archiveCutoffDate = Date::now()->subWeek()->startOfDay();

        $this->info("Starting archiving of monitoring responses older than {$archiveCutoffDate->toDateString()}");

        $archivedCount = 0;
        $deletedCount = 0;
        $chunkSize = 1000; // Process in chunks to manage memory

        DB::transaction(function () use ($archiveCutoffDate, &$archivedCount, &$deletedCount, $chunkSize) {
            MonitoringResponse::query()->where('created_at', '<', $archiveCutoffDate)
                ->chunkById($chunkSize, function ($responses) use (&$archivedCount, &$deletedCount) {
                    $dataToArchive = $responses->map(function ($response) {
                        return [
                            'id' => $response->id,
                            'monitoring_id' => $response->monitoring_id,
                            'status' => $response->status,
                            'response_time' => $response->response_time,
                            'created_at' => $response->created_at,
                            'updated_at' => $response->updated_at,
                        ];
                    })->all();

                    // Insert into archive table
                    DB::table('monitoring_response_archived')->insert($dataToArchive);
                    $archivedCount += count($dataToArchive);

                    // Delete from original table
                    MonitoringResponse::query()->whereIn('id', $responses->pluck('id'))->delete();
                    $deletedCount += count($responses);

                    $this->info("Archived {$archivedCount} and deleted {$deletedCount} responses so far...");
                });
        });

        $this->info("Finished archiving. Total archived: {$archivedCount}, Total deleted: {$deletedCount}.");

        return Command::SUCCESS;
    }
}
