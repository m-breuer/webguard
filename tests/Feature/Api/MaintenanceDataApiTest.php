<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MaintenanceDataApiTest extends TestCase
{
    public function test_maintenance_data_is_scoped_to_the_authenticated_user(): void
    {
        Date::setTestNow('2026-07-26 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visible = Monitoring::factory()->for($user)->create([
            'name' => 'Visible maintenance',
            'maintenance_from' => Date::now()->subHour(),
        ]);
        Monitoring::factory()->for($otherUser)->create([
            'name' => 'Hidden maintenance',
            'maintenance_from' => Date::now()->subHour(),
        ]);

        $this->actingAs($user)->getJson('/api/maintenance')
            ->assertOk()
            ->assertJsonPath('data.stats.total', 1)
            ->assertJsonPath('data.windows.data.0.id', $visible->id)
            ->assertJsonPath('data.windows.data.0.status', 'active')
            ->assertJsonMissing(['name' => 'Hidden maintenance']);
    }
}
