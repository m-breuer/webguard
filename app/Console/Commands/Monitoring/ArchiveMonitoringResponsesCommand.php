<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ArchiveMonitoringResponsesCommand extends Command
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
            MonitoringResponse::query()
                ->with([
                    'monitoring' => fn ($query) => $query
                        ->withTrashed()
                        ->select(['id', 'maintenance_from', 'maintenance_until']),
                ])
                ->where('created_at', '<', $archiveCutoffDate)
                ->chunkById($chunkSize, function ($responses) use (&$archivedCount, &$deletedCount) {
                    $dataToArchive = $responses->map(function (MonitoringResponse $monitoringResponse): array {
                        $status = $monitoringResponse->status->value;
                        $httpStatusCode = $monitoringResponse->http_status_code;

                        if ($this->isArchivedAsUnknown($monitoringResponse)) {
                            $status = MonitoringStatus::UNKNOWN->value;
                            $httpStatusCode = null;
                        }

                        return [
                            'id' => $monitoringResponse->id,
                            'monitoring_id' => $monitoringResponse->monitoring_id,
                            'status' => $status,
                            'http_status_code' => $httpStatusCode,
                            'response_time' => $monitoringResponse->response_time,
                            'created_at' => $monitoringResponse->created_at,
                            'updated_at' => $monitoringResponse->updated_at,
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

    private function isArchivedAsUnknown(MonitoringResponse $monitoringResponse): bool
    {
        $monitoring = $monitoringResponse->monitoring;

        if (! $monitoring instanceof Monitoring) {
            return false;
        }

        if (! $monitoringResponse->created_at instanceof CarbonInterface) {
            return false;
        }

        return $this->isUnderMaintenanceAt(
            $monitoringResponse->created_at,
            $monitoring->maintenance_from,
            $monitoring->maintenance_until
        );
    }

    private function isUnderMaintenanceAt(
        CarbonInterface $timestamp,
        ?CarbonInterface $maintenanceFrom,
        ?CarbonInterface $maintenanceUntil
    ): bool {
        if (! $maintenanceFrom) {
            return false;
        }

        if (! $maintenanceUntil) {
            return $timestamp->greaterThanOrEqualTo($maintenanceFrom);
        }

        return $timestamp->greaterThanOrEqualTo($maintenanceFrom)
            && $timestamp->lessThanOrEqualTo($maintenanceUntil);
    }
}
