<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class InternalUiDashboardApiTest extends TestCase
{
    public function test_verified_user_can_read_a_scoped_dashboard_projection(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create();
        $visibleMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Visible API']);
        Monitoring::factory()->create(['name' => 'Hidden API']);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.services.0.id', $visibleMonitoring->id)
            ->assertJsonPath('data.services.0.name', 'Visible API')
            ->assertJsonMissing(['name' => 'Hidden API'])
            ->assertJsonStructure([
                'meta' => ['as_of', 'service_pagination'],
            ]);
    }

    public function test_unverified_user_cannot_read_the_internal_ui_dashboard(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertForbidden();
    }
}
