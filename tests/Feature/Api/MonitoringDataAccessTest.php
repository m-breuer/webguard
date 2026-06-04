<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class MonitoringDataAccessTest extends TestCase
{
    public function test_shared_monitoring_data_endpoint_requires_authentication_for_private_monitoring(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertUnauthorized();
    }

    public function test_shared_monitoring_data_endpoint_requires_authentication_for_public_monitoring(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Public Monitoring',
            'public_label_enabled' => true,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertUnauthorized();
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

    public function test_validation_errors_do_not_leak_private_monitoring_existence(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/uptime-calendar');

        $testResponse->assertUnauthorized();
    }
}
