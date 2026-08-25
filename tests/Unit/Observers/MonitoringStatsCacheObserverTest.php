<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringSslResult;
use App\Observers\MonitoringStatsCacheObserver;
use App\Services\MonitoringStatsCache;
use Mockery;
use Tests\TestCase;

class MonitoringStatsCacheObserverTest extends TestCase
{
    public function test_it_invalidates_badge_data_for_each_public_monitoring_result_type(): void
    {
        $monitoring = new Monitoring();
        $monitoring->id = 'monitoring-123';

        $mock = Mockery::mock(MonitoringStatsCache::class);
        $mock->shouldReceive('flush')->times(3)->with($monitoring);
        $this->app->instance(MonitoringStatsCache::class, $mock);

        $monitoringStatsCacheObserver = resolve(MonitoringStatsCacheObserver::class);

        foreach ([new MonitoringDailyResult(), new MonitoringDomainResult(), new MonitoringSslResult()] as $result) {
            $result->setRelation('monitoring', $monitoring);
            $monitoringStatsCacheObserver->updated($result);
        }
    }
}
