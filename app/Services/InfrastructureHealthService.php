<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServerInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

class InfrastructureHealthService
{
    public const STATUS_HEALTHY = 'healthy';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    /**
     * @return array{
     *     status: string,
     *     generated_at: string,
     *     checks: array<string, array{status: string, message: string, meta: array<string, mixed>}>
     * }
     */
    public function report(): array
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'scheduler' => $this->schedulerCheck(),
            'queue' => $this->queueCheck(),
            'scanner_instances' => $this->scannerInstancesCheck(),
        ];

        return [
            'status' => $this->aggregateStatus($checks),
            'generated_at' => Date::now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    public function recordSchedulerHeartbeat(): string
    {
        $timestamp = Date::now()->toIso8601String();

        Cache::put(
            (string) config('monitoring.infrastructure_health.scheduler_cache_key'),
            $timestamp,
            Date::now()->addDays(7)
        );

        return $timestamp;
    }

    /**
     * @return array{status: string, message: string, meta: array<string, mixed>}
     */
    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return $this->check(
                self::STATUS_HEALTHY,
                'admin.infrastructure_health.checks.database.healthy',
                ['connection' => config('database.default')]
            );
        } catch (Throwable $throwable) {
            return $this->check(
                self::STATUS_CRITICAL,
                'admin.infrastructure_health.checks.database.critical',
                [
                    'connection' => config('database.default'),
                    'error' => $throwable->getMessage(),
                ]
            );
        }
    }

    /**
     * @return array{status: string, message: string, meta: array<string, mixed>}
     */
    private function cacheCheck(): array
    {
        $key = 'infrastructure-health-probe:' . Date::now()->format('YmdHisv');

        try {
            Cache::put($key, 'ok', 60);
            $healthy = Cache::get($key) === 'ok';
            Cache::forget($key);

            if (! $healthy) {
                return $this->check(
                    self::STATUS_CRITICAL,
                    'admin.infrastructure_health.checks.cache.critical',
                    ['store' => config('cache.default')]
                );
            }

            return $this->check(
                self::STATUS_HEALTHY,
                'admin.infrastructure_health.checks.cache.healthy',
                ['store' => config('cache.default')]
            );
        } catch (Throwable $throwable) {
            return $this->check(
                self::STATUS_CRITICAL,
                'admin.infrastructure_health.checks.cache.critical',
                [
                    'store' => config('cache.default'),
                    'error' => $throwable->getMessage(),
                ]
            );
        }
    }

    /**
     * @return array{status: string, message: string, meta: array<string, mixed>}
     */
    private function schedulerCheck(): array
    {
        $key = (string) config('monitoring.infrastructure_health.scheduler_cache_key');
        $staleAfterMinutes = max(1, (int) config('monitoring.infrastructure_health.scheduler_stale_after_minutes', 5));

        try {
            $lastSeenAt = Cache::get($key);
        } catch (Throwable $throwable) {
            return $this->check(
                self::STATUS_CRITICAL,
                'admin.infrastructure_health.checks.scheduler.critical',
                [
                    'cache_key' => $key,
                    'error' => $throwable->getMessage(),
                ]
            );
        }

        if (! is_string($lastSeenAt) || $lastSeenAt === '') {
            return $this->check(
                self::STATUS_WARNING,
                'admin.infrastructure_health.checks.scheduler.missing',
                [
                    'cache_key' => $key,
                    'stale_after_minutes' => $staleAfterMinutes,
                ]
            );
        }

        try {
            $lastSeen = Date::parse($lastSeenAt);
        } catch (Throwable $throwable) {
            return $this->check(
                self::STATUS_WARNING,
                'admin.infrastructure_health.checks.scheduler.invalid',
                [
                    'cache_key' => $key,
                    'stale_after_minutes' => $staleAfterMinutes,
                    'error' => $throwable->getMessage(),
                ]
            );
        }

        $minutesSinceLastSeen = (int) $lastSeen->diffInMinutes(Date::now(), true);

        if ($lastSeen->lt(Date::now()->subMinutes($staleAfterMinutes))) {
            return $this->check(
                self::STATUS_WARNING,
                'admin.infrastructure_health.checks.scheduler.stale',
                [
                    'last_seen_at' => $lastSeen->toIso8601String(),
                    'minutes_since_last_seen' => $minutesSinceLastSeen,
                    'stale_after_minutes' => $staleAfterMinutes,
                ]
            );
        }

        return $this->check(
            self::STATUS_HEALTHY,
            'admin.infrastructure_health.checks.scheduler.healthy',
            [
                'last_seen_at' => $lastSeen->toIso8601String(),
                'minutes_since_last_seen' => $minutesSinceLastSeen,
                'stale_after_minutes' => $staleAfterMinutes,
            ]
        );
    }

    /**
     * @return array{status: string, message: string, meta: array<string, mixed>}
     */
    private function queueCheck(): array
    {
        try {
            $failedJobs = DB::table((string) config('queue.failed.table', 'failed_jobs'))->count();
        } catch (Throwable $throwable) {
            return $this->check(
                self::STATUS_CRITICAL,
                'admin.infrastructure_health.checks.queue.critical',
                [
                    'connection' => config('queue.default'),
                    'error' => $throwable->getMessage(),
                ]
            );
        }

        $threshold = max(1, (int) config('monitoring.infrastructure_health.failed_jobs_warning_threshold', 1));

        if ($failedJobs >= $threshold) {
            return $this->check(
                self::STATUS_WARNING,
                'admin.infrastructure_health.checks.queue.failed_jobs',
                [
                    'connection' => config('queue.default'),
                    'failed_jobs' => $failedJobs,
                    'threshold' => $threshold,
                ]
            );
        }

        return $this->check(
            self::STATUS_HEALTHY,
            'admin.infrastructure_health.checks.queue.healthy',
            [
                'connection' => config('queue.default'),
                'failed_jobs' => $failedJobs,
                'threshold' => $threshold,
            ]
        );
    }

    /**
     * @return array{status: string, message: string, meta: array<string, mixed>}
     */
    private function scannerInstancesCheck(): array
    {
        $instances = ServerInstance::query()->get(['id', 'is_active', 'last_seen_at']);
        $activeInstances = $instances->where('is_active', true);
        $healthCounts = $instances
            ->map(fn (ServerInstance $serverInstance): string => $serverInstance->healthStatus())
            ->countBy();

        $staleInstances = (int) $healthCounts->get('stale', 0);
        $neverSeenInstances = (int) $healthCounts->get('never_seen', 0);

        $meta = [
            'total_instances' => $instances->count(),
            'active_instances' => $activeInstances->count(),
            'healthy_instances' => (int) $healthCounts->get('healthy', 0),
            'stale_instances' => $staleInstances,
            'never_seen_instances' => $neverSeenInstances,
            'inactive_instances' => (int) $healthCounts->get('inactive', 0),
        ];

        if ($activeInstances->isEmpty()) {
            return $this->check(
                self::STATUS_WARNING,
                'admin.infrastructure_health.checks.scanner_instances.none_active',
                $meta
            );
        }

        if ($staleInstances > 0 || $neverSeenInstances > 0) {
            return $this->check(
                self::STATUS_WARNING,
                'admin.infrastructure_health.checks.scanner_instances.degraded',
                $meta
            );
        }

        return $this->check(
            self::STATUS_HEALTHY,
            'admin.infrastructure_health.checks.scanner_instances.healthy',
            $meta
        );
    }

    /**
     * @param  array<string, array{status: string, message: string, meta: array<string, mixed>}>  $checks
     */
    private function aggregateStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array(self::STATUS_CRITICAL, $statuses, true)) {
            return self::STATUS_CRITICAL;
        }

        if (in_array(self::STATUS_WARNING, $statuses, true)) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_HEALTHY;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{status: string, message: string, meta: array<string, mixed>}
     */
    private function check(string $status, string $message, array $meta = []): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'meta' => $meta,
        ];
    }
}
