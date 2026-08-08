<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServerHealthReportRequest;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;

class ServerHealthReportController extends Controller
{
    /**
     * Store a server health report.
     *
     * Use the private endpoint generated for a Server Health monitoring. Send
     * schema_version 1 reports from your server agent. These contain host CPU,
     * RAM, load, uptime, and optional service checks. WebGuard derives health
     * from configured thresholds and the reported metrics. The flat legacy
     * payload remains accepted for existing scripts.
     *
     * @group Server Health
     *
     * @unauthenticated
     *
     * @urlParam token string required The private server health token generated when the monitoring is created. Example: 01HXZ7Q92W3K7VY9E6JQFM4XPC
     *
     * @bodyParam schema_version integer required Versioned report schema. Example: 1
     * @bodyParam report_id string required UUID generated once per report for safe retries. Example: 5c1a64a0-94ba-4a8f-8f20-a7c50f286d77
     * @bodyParam sampled_at datetime required RFC 3339 timestamp measured by the agent. Example: 2026-08-08T12:00:00Z
     * @bodyParam host object required Host metrics. Filesystem, mount, and process data are not accepted.
     * @bodyParam host.cpu_usage_percent number Optional CPU usage percentage. Example: 42.5
     * @bodyParam host.ram_usage_percent number Optional RAM usage percentage. Example: 68.2
     * @bodyParam host.logical_cpu_count integer Optional logical CPU count. Example: 4
     * @bodyParam host.load_average_1m number Optional one-minute load average. Example: 1.42
     * @bodyParam host.uptime_seconds integer Optional uptime in seconds. Example: 86400
     * @bodyParam service_checks array Optional application or endpoint checks (maximum 20).
     *
     * @response {
     *   "message": "Server health report accepted.",
     *   "status": "up",
     *   "metrics": {
     *     "cpu_usage_percent": 42.5,
     *     "ram_usage_percent": 68.2,
     *     "logical_cpu_count": 4,
     *     "load_average_1m": 1.42
     *   }
     * }
     */
    public function __invoke(ServerHealthReportRequest $serverHealthReportRequest, string $token): JsonResponse
    {
        $monitoring = Monitoring::query()
            ->where('type', MonitoringType::SERVER_HEALTH->value)
            ->where('server_health_token', $token)
            ->firstOrFail();

        return $this->store($serverHealthReportRequest, $monitoring);
    }

    public function store(ServerHealthReportRequest $serverHealthReportRequest, Monitoring $monitoring): JsonResponse
    {

        $validated = $serverHealthReportRequest->validated();

        $metrics = $this->extractMetrics($validated);

        if ($metrics === [] && ! isset($validated['status'])) {
            return response()->json([
                'message' => 'Provide at least one server health metric or an explicit status.',
                'errors' => [
                    'metrics' => ['Provide at least one server health metric or an explicit status.'],
                ],
            ], 422);
        }

        $legacyStatus = isset($validated['status']) ? MonitoringStatus::from($validated['status']) : null;
        $evaluatedStatus = $metrics !== []
            ? $this->statusFromMetrics($metrics, $monitoring)
            : ($legacyStatus ?? MonitoringStatus::UNKNOWN);

        $timestamp = Date::now();
        $reportId = $validated['report_id'] ?? null;
        $attributes = [
            'monitoring_id' => $monitoring->id,
            'status' => $metrics === [] ? $legacyStatus : null,
            'http_status_code' => null,
            'response_time' => null,
            'server_health_metrics' => $metrics,
            'server_health_report_id' => $reportId,
            'server_health_sampled_at' => isset($validated['sampled_at']) ? Date::parse($validated['sampled_at']) : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        $monitoringResponse = $reportId !== null
            ? MonitoringResponse::query()->firstOrCreate([
                'monitoring_id' => $monitoring->id,
                'server_health_report_id' => $reportId,
            ], $attributes)
            : MonitoringResponse::query()->create($attributes);

        if ($monitoringResponse->wasRecentlyCreated || $reportId === null) {
            $monitoring->forceFill([
                'server_health_last_reported_at' => $timestamp,
            ])->save();
        }

        $isDuplicate = $reportId !== null && ! $monitoringResponse->wasRecentlyCreated;
        $responseMetrics = $isDuplicate ? ($monitoringResponse->server_health_metrics ?? []) : $metrics;

        return response()->json([
            'message' => ! $isDuplicate
                ? 'Server health report accepted.'
                : 'Server health report already accepted.',
            'status' => (! $isDuplicate ? $evaluatedStatus : $this->statusFromMetrics(
                $responseMetrics,
                $monitoring,
            ))->value,
            'metrics' => $responseMetrics,
            'deduplicated' => $isDuplicate,
            'thresholds' => [
                'cpu_usage_percent' => $this->thresholdFor($monitoring, 'server_health_cpu_threshold_percent'),
                'ram_usage_percent' => $this->thresholdFor($monitoring, 'server_health_ram_threshold_percent'),
                'storage_usage_percent' => $this->thresholdFor($monitoring, 'server_health_storage_threshold_percent'),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function extractMetrics(array $validated): array
    {
        if (isset($validated['schema_version'])) {
            $metrics = array_filter($validated['host'] ?? [], static fn (mixed $value): bool => $value !== null);

            if (isset($validated['service_checks']) && $validated['service_checks'] !== []) {
                $metrics['service_checks'] = array_map(static fn (array $serviceCheck): array => array_filter(
                    $serviceCheck,
                    static fn (mixed $value): bool => $value !== null,
                ), $validated['service_checks']);
            }

            if (isset($validated['agent']['version'])) {
                $metrics['agent'] = ['version' => $validated['agent']['version']];
            }

            return $metrics;
        }

        $metricKeys = [
            'cpu_usage_percent',
            'ram_usage_percent',
            'storage_usage_percent',
            'load_average',
            'uptime_seconds',
        ];

        $metrics = [];

        foreach ($metricKeys as $metricKey) {
            if (array_key_exists($metricKey, $validated) && $validated[$metricKey] !== null) {
                $metrics[$metricKey] = is_numeric($validated[$metricKey])
                    ? (float) $validated[$metricKey]
                    : $validated[$metricKey];
            }
        }

        if (isset($validated['extra_metrics']) && is_array($validated['extra_metrics'])) {
            $metrics['extra_metrics'] = $validated['extra_metrics'];
        }

        return $metrics;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function statusFromMetrics(array $metrics, Monitoring $monitoring): MonitoringStatus
    {
        $thresholds = [
            'cpu_usage_percent' => $this->thresholdFor($monitoring, 'server_health_cpu_threshold_percent'),
            'ram_usage_percent' => $this->thresholdFor($monitoring, 'server_health_ram_threshold_percent'),
            'storage_usage_percent' => $this->thresholdFor($monitoring, 'server_health_storage_threshold_percent'),
        ];

        foreach ($thresholds as $key => $threshold) {
            if (isset($metrics[$key]) && (float) $metrics[$key] >= $threshold) {
                return MonitoringStatus::DOWN;
            }
        }

        $serviceChecks = $metrics['service_checks'] ?? [];

        if (is_array($serviceChecks) && collect($serviceChecks)->contains(
            static fn (mixed $serviceCheck): bool => is_array($serviceCheck) && ($serviceCheck['success'] ?? true) === false,
        )) {
            return MonitoringStatus::DOWN;
        }

        $logicalCpuCount = $metrics['logical_cpu_count'] ?? null;
        $loadAverage = $metrics['load_average_1m'] ?? null;
        $loadThreshold = $monitoring->server_health_load_threshold_per_cpu;

        if (is_numeric($logicalCpuCount) && (int) $logicalCpuCount > 0 && is_numeric($loadAverage) && $loadThreshold !== null
            && ((float) $loadAverage / (int) $logicalCpuCount) >= $loadThreshold) {
            return MonitoringStatus::DOWN;
        }

        return MonitoringStatus::UP;
    }

    private function thresholdFor(Monitoring $monitoring, string $attribute): float
    {
        $threshold = $monitoring->getAttribute($attribute);

        return is_numeric($threshold) ? (float) $threshold : 90.0;
    }
}
