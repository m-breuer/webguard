<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class InternalUiDashboardApiTest extends TestCase
{
    public function test_guest_cannot_read_the_internal_ui_dashboard(): void
    {
        $this->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertUnauthorized();
    }

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
            ->assertHeader('X-Request-Id')
            ->assertHeader('X-Query-Count')
            ->assertHeader('X-Response-Bytes')
            ->assertHeader('Server-Timing')
            ->assertJsonStructure([
                'meta' => ['as_of', 'service_pagination'],
            ]);
    }

    public function test_dashboard_projection_stays_within_its_response_budget(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create();
        Monitoring::factory()->count(3)->for($user)->create();

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'));

        $testResponse->assertOk();
        $this->assertLessThanOrEqual(30, (int) $testResponse->headers->get('X-Query-Count'));
        $this->assertLessThanOrEqual(131072, (int) $testResponse->headers->get('X-Response-Bytes'));
    }

    public function test_unverified_user_cannot_read_the_internal_ui_dashboard(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_projection_supports_private_conditional_get_requests(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create();
        Monitoring::factory()->for($user)->create(['name' => 'Visible API']);

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'));
        $etag = (string) $testResponse->headers->get('ETag');

        $testResponse
            ->assertOk();
        $this->assertNotSame('', $etag);
        $this->assertStringContainsString('private', (string) $testResponse->headers->get('Cache-Control'));

        $this->actingAs($user)
            ->withHeader('If-None-Match', $etag)
            ->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertNotModified()
            ->assertHeader('ETag', $etag);
    }
}
