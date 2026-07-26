<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Data\MonitoringIndexFilters;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use App\Queries\MonitoringIndexQuery;
use Tests\TestCase;

class MonitoringIndexQueryTest extends TestCase
{
    public function test_it_paginates_only_monitorings_visible_to_the_actor(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visibleMonitorings = Monitoring::factory()->count(3)->for($user)->create();
        Monitoring::factory()->for($otherUser)->create(['name' => 'Hidden monitoring']);

        $readModel = resolve(MonitoringIndexQuery::class)->for($user, $this->filters(), 2);

        $this->assertSame(3, $readModel->total);
        $this->assertCount(2, $readModel->monitorings);
        $this->assertEqualsCanonicalizing($visibleMonitorings->modelKeys(), $readModel->summaryMonitoringIds->all());
        $this->assertNotContains('Hidden monitoring', $readModel->monitorings->pluck('name')->all());
    }

    private function filters(): MonitoringIndexFilters
    {
        return new MonitoringIndexFilters(
            search: null,
            types: [],
            healthStatuses: [],
            lifecycleStatus: null,
            groupId: null,
            teamId: null,
            ownership: null,
            onlyActiveMaintenance: false,
            sort: null,
        );
    }
}
