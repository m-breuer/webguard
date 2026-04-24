<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class MonitoringDataAccessTest extends TestCase
{
    public function test_shared_monitoring_data_endpoint_blocks_private_monitoring_without_authentication(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertNotFound();
    }

    public function test_shared_monitoring_data_endpoint_allows_public_monitoring_without_authentication(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Public API',
            'public_label_enabled' => true,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertOk()
            ->assertJsonPath('monitoring.name', 'Public API');
    }

    public function test_shared_monitoring_data_endpoint_allows_private_monitoring_for_owner(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Private API',
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertOk()
            ->assertJsonPath('monitoring.name', 'Private API');
    }

    public function test_shared_monitoring_data_endpoint_blocks_private_monitoring_for_another_user(): void
    {
        Package::factory()->create();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $monitoring = Monitoring::factory()->for($owner)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->actingAs($otherUser)->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertNotFound();
    }
}
