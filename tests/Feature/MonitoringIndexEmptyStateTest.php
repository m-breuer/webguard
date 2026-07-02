<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        $testResponse->assertSeeHtml('class="grid w-full grid-cols-1 gap-2 sm:ml-auto sm:grid-cols-2 md:flex md:w-auto md:flex-wrap md:justify-end md:gap-3"');
        $testResponse->assertSeeHtml('class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 md:w-auto"');
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
}
