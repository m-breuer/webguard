<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Services\MonitoringHealthEvaluator;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

#[Description('Archives monitoring responses for a given date (defaults to yesterday) by moving them to a separate table and deleting them from the live table.')]
#[Signature('monitoring:archive-responses')]
class ArchiveMonitoringResponsesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MonitoringHealthEvaluator $monitoringHealthEvaluator)
    {
        $archiveCutoffDate = Date::now()->subWeek()->startOfDay();

        $this->info("Starting archiving of monitoring responses older than {$archiveCutoffDate->toDateString()}");

        $archivedCount = 0;
        $deletedCount = 0;
        $chunkSize = 1000; // Process in chunks to manage memory

        DB::transaction(function () use ($archiveCutoffDate, &$archivedCount, &$deletedCount, $chunkSize, $monitoringHealthEvaluator) {
            MonitoringResponse::query()
                ->with([
                    'monitoring' => fn ($query) => $query
                        ->withTrashed()
                        ->select([
                            'id',
                            'type',
                            'expected_http_statuses',
                            'dns_expected_values',
                            'server_health_cpu_threshold_percent',
                            'server_health_ram_threshold_percent',
                            'server_health_storage_threshold_percent',
                            'server_health_load_threshold_per_cpu',
                            'maintenance_from',
                            'maintenance_until',
                        ]),
                ])
                ->where('created_at', '<', $archiveCutoffDate)
                ->chunkById($chunkSize, function ($responses) use (&$archivedCount, &$deletedCount, $monitoringHealthEvaluator) {
                    $dataToArchive = $responses->map(function (MonitoringResponse $monitoringResponse) use ($monitoringHealthEvaluator): array {
                        $status = $monitoringHealthEvaluator->availabilityFor($monitoringResponse->monitoring, $monitoringResponse)->value;
                        $httpStatusCode = $monitoringResponse->http_status_code;

                        if ($this->isArchivedAsUnknown($monitoringResponse)) {
                            $status = MonitoringStatus::UNKNOWN->value;
                            $httpStatusCode = null;
                        }

                        return [
                            'id' => $monitoringResponse->id,
                            'monitoring_id' => $monitoringResponse->monitoring_id,
                            'location_code' => $monitoringResponse->location_code,
                            'status' => $status,
                            'http_status_code' => $httpStatusCode,
                            'response_time' => $monitoringResponse->response_time,
                            'check_interval_seconds' => $monitoringResponse->check_interval_seconds,
                            'server_health_metrics' => $monitoringResponse->server_health_metrics !== null
                                ? json_encode($monitoringResponse->server_health_metrics, JSON_THROW_ON_ERROR)
                                : null,
                            'server_health_report_id' => $monitoringResponse->server_health_report_id,
                            'server_health_sampled_at' => $monitoringResponse->server_health_sampled_at,
                            'vital_values' => $monitoringResponse->vital_values !== null
                                ? json_encode($monitoringResponse->vital_values, JSON_THROW_ON_ERROR)
                                : null,
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
