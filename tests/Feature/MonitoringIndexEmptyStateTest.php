<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class MonitoringIndexEmptyStateTest extends TestCase
{
    public function test_guest_user_sees_empty_state_without_create_button(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $guestUser = User::factory()->create([
            'package_id' => $package->id,
            'role' => UserRole::GUEST->value,
        ]);

        $testResponse = $this->actingAs($guestUser)->get(route('monitorings.index'));

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

    public function test_guest_user_cannot_access_monitoring_create_route(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $guestUser = User::factory()->create([
            'package_id' => $package->id,
            'role' => UserRole::GUEST->value,
        ]);

        $testResponse = $this->actingAs($guestUser)->get(route('monitorings.create'));

        $testResponse->assertForbidden();
    }
}
