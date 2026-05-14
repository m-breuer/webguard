<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ComponentStatusPageTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_public_component_status_page_groups_monitorings_and_aggregates_status(): void
    {
        Date::setTestNow('2026-05-14 09:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $apiMonitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://api.example.test/secret-path',
            'created_at' => Date::now()->subDays(10),
        ]);
        $workerMonitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Queue Worker',
            'created_at' => Date::now()->subDays(10),
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $apiMonitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 120,
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ]);
        MonitoringResponse::query()->create([
            'monitoring_id' => $workerMonitoring->id,
            'status' => MonitoringStatus::DOWN,
            'response_time' => null,
            'created_at' => Date::now()->subMinutes(3),
            'updated_at' => Date::now()->subMinutes(3),
        ]);
        Incident::query()->create([
            'monitoring_id' => $workerMonitoring->id,
            'down_at' => Date::now()->subMinutes(30),
            'up_at' => null,
        ]);

        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'description' => 'Public operating status',
            'is_public' => true,
        ]);
        $apiComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $apiComponent->monitorings()->attach($apiMonitoring->id, ['position' => 0]);
        $workerComponent = $statusPage->components()->create(['name' => 'Workers', 'position' => 1]);
        $workerComponent->monitorings()->attach($workerMonitoring->id, ['position' => 0]);

        $response = $this->get(route('public-status-pages.show', $statusPage->slug));

        $response->assertOk();
        $response->assertSeeText('Acme Status');
        $response->assertSeeText(__('status_page.public.overall_status') . ': DOWN');
        $response->assertSeeTextInOrder(['API', 'UP', 'Workers', 'DOWN']);
        $response->assertSeeText('Primary API');
        $response->assertSeeText('Queue Worker');
        $response->assertSeeText(__('status_page.public.recent_incidents'));
        $response->assertSeeText(__('monitoring.public_label.ongoing'));
        $response->assertDontSeeText('https://api.example.test/secret-path');
    }

    public function test_private_component_status_page_returns_not_found_publicly(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Private Status',
            'slug' => 'private-status',
            'is_public' => false,
        ]);

        $response = $this->get(route('public-status-pages.show', $statusPage->slug));

        $response->assertNotFound();
    }
}
