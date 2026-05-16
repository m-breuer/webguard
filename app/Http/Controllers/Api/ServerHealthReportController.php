<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServerHealthReportController extends Controller
{
    /**
     * Store a server health report.
     *
     * Use the private endpoint generated for a Server Health monitoring. Send
     * CPU, RAM, and storage percentages from your server agent or cron script.
     * If no explicit status is supplied, WebGuard marks the report down when
     * any percentage metric reaches that monitor's configured threshold.
     *
     * @group Server Health
     *
     * @unauthenticated
     *
     * @urlParam token string required The private server health token generated when the monitoring is created. Example: 01HXZ7Q92W3K7VY9E6JQFM4XPC
     *
     * @bodyParam status string Optional explicit status. One of up, down, unknown. Example: up
     * @bodyParam cpu_usage_percent number CPU usage as a percentage from 0 to 100. Example: 42.5
     * @bodyParam ram_usage_percent number RAM usage as a percentage from 0 to 100. Example: 68.2
     * @bodyParam storage_usage_percent number Storage usage as a percentage from 0 to 100. Example: 74.1
     * @bodyParam load_average number Optional system load average. Example: 1.42
     * @bodyParam uptime_seconds integer Optional server uptime in seconds. Example: 86400
     * @bodyParam extra_metrics object Optional additional numeric or string metrics. Example: {"swap_usage_percent": 12.4}
     *
     * @response {
     *   "message": "Server health report accepted.",
     *   "status": "up",
     *   "metrics": {
     *     "cpu_usage_percent": 42.5,
     *     "ram_usage_percent": 68.2,
     *     "storage_usage_percent": 74.1
     *   }
     * }
     */
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $monitoring = Monitoring::query()
            ->where('type', MonitoringType::SERVER_HEALTH->value)
            ->where('server_health_token', $token)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(MonitoringStatus::class)],
            'cpu_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ram_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'storage_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'load_average' => ['nullable', 'numeric', 'min:0'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'extra_metrics' => ['nullable', 'array'],
            'extra_metrics.*' => ['nullable'],
        ]);

        $metrics = $this->extractMetrics($validated);

        if ($metrics === [] && ! isset($validated['status'])) {
            return response()->json([
                'message' => 'Provide at least one server health metric or an explicit status.',
                'errors' => [
                    'metrics' => ['Provide at least one server health metric or an explicit status.'],
                ],
            ], 422);
        }

        $status = isset($validated['status'])
            ? MonitoringStatus::from($validated['status'])
            : $this->statusFromMetrics($metrics, $monitoring);

        $timestamp = now();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => $status,
            'http_status_code' => match ($status) {
                MonitoringStatus::UP => 200,
                MonitoringStatus::DOWN => 503,
                MonitoringStatus::UNKNOWN => null,
            },
            'response_time' => null,
            'server_health_metrics' => $metrics,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $monitoring->forceFill([
            'server_health_last_reported_at' => $timestamp,
        ])->save();

        return response()->json([
            'message' => 'Server health report accepted.',
            'status' => $status->value,
            'metrics' => $metrics,
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

        return MonitoringStatus::UP;
    }

    private function thresholdFor(Monitoring $monitoring, string $attribute): float
    {
        $threshold = $monitoring->getAttribute($attribute);

        return is_numeric($threshold) ? (float) $threshold : 90.0;
    }
}
