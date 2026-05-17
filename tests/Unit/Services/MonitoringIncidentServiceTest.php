<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringIncidentPayload;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringIncidentService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringIncidentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_incidents_are_filtered_by_down_date_and_formatted_newest_first(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring();

        $this->createIncident($monitoring, '2026-04-10 09:00:00', '2026-04-10 09:15:00');
        $this->createIncident($monitoring, '2026-04-11 10:00:00', null);
        $this->createIncident($monitoring, '2026-04-01 10:00:00', '2026-04-01 10:05:00');

        $incidents = resolve(MonitoringIncidentService::class)->getIncidents(
            $monitoring,
            Date::parse('2026-04-10'),
            Date::parse('2026-04-11')
        );

        $this->assertContainsOnlyInstancesOf(MonitoringIncidentPayload::class, $incidents);
        $this->assertCount(2, $incidents);
        $this->assertSame('2026-04-11T10:00:00+02:00', $incidents[0]->downAt);
        $this->assertNull($incidents[0]->upAt);
        $this->assertSame('2026-04-10T09:00:00+02:00', $incidents[1]->downAt);
        $this->assertSame('2026-04-10T09:15:00+02:00', $incidents[1]->upAt);
    }

    private function createMonitoring(): Monitoring
    {
        Package::factory()->create();
        $user = User::factory()->create();

        return Monitoring::factory()->for($user)->create();
    }

    private function createIncident(Monitoring $monitoring, string $downAt, ?string $upAt): void
    {
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::parse($downAt),
            'up_at' => $upAt ? Date::parse($upAt) : null,
        ]);
    }
}
