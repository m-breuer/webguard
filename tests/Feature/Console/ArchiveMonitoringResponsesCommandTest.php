<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ArchiveMonitoringResponsesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_archives_maintenance_responses_as_unknown_instead_of_down(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();

        $monitoringInMaintenance = Monitoring::factory()->for($user)->create([
            'maintenance_from' => Date::parse('2026-03-16 00:00:00'),
            'maintenance_until' => Date::parse('2026-03-16 23:59:59'),
        ]);

        $monitoringWithoutMaintenance = Monitoring::factory()->for($user)->create();

        $maintenanceResponse = MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoringInMaintenance->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => null,
            'created_at' => Date::parse('2026-03-16 12:00:00'),
            'updated_at' => Date::parse('2026-03-16 12:00:00'),
        ]);

        $regularResponse = MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoringWithoutMaintenance->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => null,
            'created_at' => Date::parse('2026-03-16 13:00:00'),
            'updated_at' => Date::parse('2026-03-16 13:00:00'),
        ]);

        Artisan::call('monitoring:archive-responses');

        $this->assertDatabaseMissing('monitoring_response_results', ['id' => $maintenanceResponse->id]);
        $this->assertDatabaseMissing('monitoring_response_results', ['id' => $regularResponse->id]);

        $this->assertDatabaseHas('monitoring_response_archived', [
            'id' => $maintenanceResponse->id,
            'monitoring_id' => $monitoringInMaintenance->id,
            'status' => MonitoringStatus::UNKNOWN->value,
            'http_status_code' => null,
        ]);

        $this->assertDatabaseHas('monitoring_response_archived', [
            'id' => $regularResponse->id,
            'monitoring_id' => $monitoringWithoutMaintenance->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
        ]);
    }
}
