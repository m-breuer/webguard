<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Monitoring;
use App\Models\TeamMembership;
use App\Observers\OperationsOverviewCacheObserver;
use App\Services\MonitoringStatsCache;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class OperationsOverviewCacheObserverTest extends TestCase
{
    public function test_it_flushes_the_operations_overview_after_monitoring_changes(): void
    {
        $this->expectOverviewFlush();
        $this->expectMonitoringStatsFlush();

        $monitoring = new Monitoring();
        $monitoring->id = 'monitoring-123';

        resolve(OperationsOverviewCacheObserver::class)->updated($monitoring);
    }

    public function test_it_flushes_the_operations_overview_after_membership_changes(): void
    {
        $this->expectOverviewFlush();

        resolve(OperationsOverviewCacheObserver::class)->deleted(new TeamMembership());
    }

    private function expectOverviewFlush(): void
    {
        $mock = Mockery::mock(TaggableStore::class);
        $taggedCache = Mockery::mock();

        Cache::shouldReceive('getStore')->once()->andReturn($mock);
        Cache::shouldReceive('tags')->once()->with(['operations-overview'])->andReturn($taggedCache);
        $taggedCache->shouldReceive('flush')->once();
    }

    private function expectMonitoringStatsFlush(): void
    {
        $monitoringStatsCache = Mockery::mock(MonitoringStatsCache::class);
        $monitoringStatsCache->shouldReceive('flush')->once()->with(Mockery::on(static fn (Monitoring $monitoring): bool => $monitoring->id === 'monitoring-123'));

        $this->app->instance(MonitoringStatsCache::class, $monitoringStatsCache);
    }
}
