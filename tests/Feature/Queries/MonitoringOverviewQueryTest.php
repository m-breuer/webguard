<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use App\Queries\MonitoringOverviewQuery;
use Tests\TestCase;

class MonitoringOverviewQueryTest extends TestCase
{
    public function test_it_returns_only_monitorings_visible_to_the_requested_user(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visibleMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Visible API']);
        Monitoring::factory()->for($otherUser)->create(['name' => 'Hidden API']);

        $monitorings = resolve(MonitoringOverviewQuery::class)->monitoringsFor($user);

        $this->assertSame([$visibleMonitoring->id], $monitorings->modelKeys());
        $this->assertTrue($monitorings->firstOrFail()->relationLoaded('latestResponseResult'));
        $this->assertTrue($monitorings->firstOrFail()->relationLoaded('latestIncident'));
        $this->assertTrue($monitorings->firstOrFail()->relationLoaded('groups'));
    }
}
