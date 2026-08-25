<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Incident;
use App\Models\Monitoring;
use App\Observers\IncidentObserver;
use App\Services\MonitoringStatsCache;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class IncidentObserverTest extends TestCase
{
    public function test_it_invalidates_public_monitoring_cache_after_an_incident_update(): void
    {
        $monitoring = new Monitoring();
        $monitoring->id = 'monitoring-123';

        $incident = new Incident(['monitoring_id' => $monitoring->id]);
        $incident->setRelation('monitoring', $monitoring);

        $this->expectOverviewFlush();

        $mock = Mockery::mock(MonitoringStatsCache::class);
        $mock->shouldReceive('flush')->once()->with($monitoring);
        $this->app->instance(MonitoringStatsCache::class, $mock);

        resolve(IncidentObserver::class)->updated($incident);
    }

    public function test_it_ignores_an_incident_without_a_resolved_monitoring(): void
    {
        $this->expectOverviewFlush();

        resolve(IncidentObserver::class)->updated(new Incident());
    }

    private function expectOverviewFlush(): void
    {
        $mock = Mockery::mock(TaggableStore::class);
        $taggedCache = Mockery::mock();

        Cache::shouldReceive('getStore')->once()->andReturn($mock);
        Cache::shouldReceive('tags')->once()->with(['operations-overview'])->andReturn($taggedCache);
        $taggedCache->shouldReceive('flush')->once();
    }
}
