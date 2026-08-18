<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringStatus;
use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonitoringIndexEmptyStateTest extends TestCase
{
    public function test_demo_user_sees_empty_state_without_create_button(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $demoUser = User::factory()->create([
            'package_id' => $package->id,
            'role' => UserRole::DEMO->value,
        ]);

        $testResponse = $this->actingAs($demoUser)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSee(__('monitoring.no_monitoring.title'));
        $testResponse->assertSee(__('monitoring.no_monitoring.text'));
        $testResponse->assertDontSeeHtml('href="' . route('monitorings.create') . '"');
    }

    public function test_regular_user_sees_empty_state_with_create_button(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSee(__('monitoring.no_monitoring.title'));
        $testResponse->assertSee(__('monitoring.no_monitoring.text'));
        $testResponse->assertSeeHtml('href="' . route('monitorings.create') . '"');
    }

    public function test_monitoring_filters_render_with_mobile_friendly_wrapping(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:flex md:w-auto md:flex-wrap md:gap-3"');
        $testResponse->assertSeeHtml('class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 md:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"');
    }

    public function test_monitoring_index_uses_the_operations_workspace_layout(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->for($user)->create(['name' => 'Operations service']);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSee(__('monitoring.index.workspace.active'));
        $testResponse->assertSee(__('monitoring.index.workspace.open_incidents'));
        $testResponse->assertSee(__('monitoring.index.workspace.status_pages'));
        $testResponse->assertSeeHtml('href="' . route('incidents.analytics') . '"');
        $testResponse->assertSeeHtml('href="' . route('status-pages.index') . '"');
    }

    public function test_monitoring_index_uses_the_shared_table_pagination(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->count(6)->for($user)->create();

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));
        $previousPage = $this->actingAs($user)->get(route('monitorings.index', ['page' => 2]));

        $testResponse->assertOk()
            ->assertSeeHtml('data-table-pagination')
            ->assertSeeText('1 / 2')
            ->assertSeeText(__('pagination.next'))
            ->assertSeeHtml('data-pagination-icon="next"')
            ->assertDontSee('&raquo;');
        $previousPage->assertOk()
            ->assertSeeText('2 / 2')
            ->assertSeeText(__('pagination.previous'))
            ->assertSeeHtml('data-pagination-icon="previous"')
            ->assertDontSee('&laquo;');
    }

    public function test_default_monitoring_index_reuses_paginator_total_without_extra_monitoring_count_query(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();

        $queries = collect(DB::getQueryLog())
            ->pluck('query');

        $monitoringCountQueries = $queries
            ->filter(static fn (string $query): bool => str_starts_with($query, 'select count(*) as aggregate from "monitorings"'))
            ->count();

        $this->assertLessThanOrEqual(1, $monitoringCountQueries, $queries->implode(PHP_EOL));

        $summaryIdQueries = $queries
            ->filter(static fn (string $query): bool => preg_match('/select ["`]?id["`]? from ["`]?monitorings["`]?/i', $query) === 1);

        $this->assertNotEmpty($summaryIdQueries, $queries->implode(PHP_EOL));
        $this->assertTrue(
            $summaryIdQueries->every(static fn (string $query): bool => ! str_contains(mb_strtolower($query), 'order by')),
            $summaryIdQueries->implode(PHP_EOL)
        );
    }

    public function test_monitoring_index_loads_maintenance_state_without_one_query_per_monitoring(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->count(6)->for($user)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();

        $maintenanceQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(static fn (string $query): bool => str_contains($query, 'maintenance_windows'));

        $this->assertCount(1, $maintenanceQueries, $maintenanceQueries->implode(PHP_EOL));
    }

    public function test_demo_user_cannot_access_monitoring_create_route(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $demoUser = User::factory()->create([
            'package_id' => $package->id,
            'role' => UserRole::DEMO->value,
        ]);

        $testResponse = $this->actingAs($demoUser)->get(route('monitorings.create'));

        $testResponse->assertForbidden();
    }

    public function test_monitoring_index_supports_health_preset_and_exposes_operational_summary(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $downMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Down service']);
        $healthyMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Healthy service']);

        MonitoringResponse::query()->create([
            'monitoring_id' => $downMonitoring->id,
            'status' => MonitoringStatus::DOWN,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        MonitoringResponse::query()->create([
            'monitoring_id' => $healthyMonitoring->id,
            'status' => MonitoringStatus::UP,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index', ['health' => 'down']));

        $testResponse->assertOk();
        $testResponse->assertSee('Down service');
        $testResponse->assertDontSee('Healthy service');
        $testResponse->assertSee(__('monitoring.index.table.summary'));
        $testResponse->assertSee(__('monitoring.index.filters.clear'));
    }

    public function test_monitoring_response_results_have_an_index_for_latest_status_filters(): void
    {
        $indexColumns = collect(Schema::getIndexes('monitoring_response_results'))
            ->map(static fn (array $index): array => $index['columns'])
            ->values();

        $this->assertTrue(
            $indexColumns->contains(['monitoring_id', 'id', 'status']),
            'Expected monitoring_response_results to have an index on (monitoring_id, id, status).'
        );
    }

    public function test_operational_summary_includes_monitorings_from_all_result_pages(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitorings = Monitoring::factory()->count(6)->for($user)->sequence(
            ['name' => 'Monitoring 1'],
            ['name' => 'Monitoring 2'],
            ['name' => 'Monitoring 3'],
            ['name' => 'Monitoring 4'],
            ['name' => 'Monitoring 5'],
            ['name' => 'Monitoring 6'],
        )->create();

        foreach ($monitorings as $monitoring) {
            MonitoringResponse::query()->create([
                'monitoring_id' => $monitoring->id,
                'status' => $monitoring->name === 'Monitoring 6' ? MonitoringStatus::DOWN : MonitoringStatus::UP,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ]);
        }

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));
        $summaryMonitoringIds = $testResponse->viewData('summaryMonitoringIds');
        $pageMonitoringIds = $testResponse->viewData('monitorings')->getCollection()->pluck('id')->all();

        $this->assertCount(6, $summaryMonitoringIds);
        $this->assertCount(5, $pageMonitoringIds);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.cards', [
            'ids' => $summaryMonitoringIds->all(),
        ]))
            ->assertOk()
            ->assertJsonPath('summary.attention', 1)
            ->assertJsonPath('summary.healthy', 5);
    }

    public function test_monitoring_index_supports_active_maintenance_preset(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $maintenanceMonitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Maintenance service',
            'maintenance_from' => now()->subMinute(),
            'maintenance_until' => now()->addMinute(),
        ]);
        Monitoring::factory()->for($user)->create(['name' => 'Regular service']);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index', ['maintenance' => 'active']));

        $testResponse->assertOk();
        $testResponse->assertSee($maintenanceMonitoring->name);
        $testResponse->assertDontSee('Regular service');
    }
}
