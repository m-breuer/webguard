<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use App\Queries\MonitoringCardQuery;
use App\Queries\MonitoringDetailQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class MonitoringReadQueriesTest extends TestCase
{
    public function test_detail_query_only_resolves_monitorings_visible_to_the_actor(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visible = Monitoring::factory()->for($user)->create();
        $hidden = Monitoring::factory()->for($otherUser)->create();

        $query = app(MonitoringDetailQuery::class);

        $this->assertTrue($query->findVisible($user, $visible->id)->is($visible));
        $this->expectException(ModelNotFoundException::class);

        $query->findVisible($user, $hidden->id);
    }

    public function test_card_query_only_returns_requested_monitorings_visible_to_the_actor(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visible = Monitoring::factory()->for($user)->create();
        $hidden = Monitoring::factory()->for($otherUser)->create();

        $monitorings = app(MonitoringCardQuery::class)->for($user, [$visible->id, $hidden->id]);

        $this->assertSame([$visible->id], $monitorings->modelKeys());
        $this->assertTrue($monitorings->first()->relationLoaded('latestIncident'));
        $this->assertTrue($monitorings->first()->relationLoaded('latestResponseResult'));
    }
}
