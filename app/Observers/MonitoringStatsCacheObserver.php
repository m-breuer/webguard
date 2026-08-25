<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MonitoringDailyResult;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringSslResult;
use App\Services\MonitoringStatsCache;

final class MonitoringStatsCacheObserver
{
    public function created(MonitoringDailyResult|MonitoringDomainResult|MonitoringSslResult $result): void
    {
        $this->flush($result);
    }

    public function updated(MonitoringDailyResult|MonitoringDomainResult|MonitoringSslResult $result): void
    {
        $this->flush($result);
    }

    public function deleted(MonitoringDailyResult|MonitoringDomainResult|MonitoringSslResult $result): void
    {
        $this->flush($result);
    }

    private function flush(MonitoringDailyResult|MonitoringDomainResult|MonitoringSslResult $result): void
    {
        resolve(MonitoringStatsCache::class)->flush($result->monitoring);
    }
}
