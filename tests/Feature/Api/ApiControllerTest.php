<?php

declare(strict_types=1);

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the correct interval in the status endpoint', function () {
    Package::factory()->create();
    $user = User::factory()->create();
    $monitoring = Monitoring::factory()->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/status');

    $response->assertOk();
    $response->assertJson(['interval' => 300]);
});
