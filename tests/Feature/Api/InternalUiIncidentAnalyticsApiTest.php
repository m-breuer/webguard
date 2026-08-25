<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\IncidentCustomerImpact;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

class InternalUiIncidentAnalyticsApiTest extends TestCase
{
    use AssertsApiContracts;

    public function test_guest_cannot_read_incident_analytics(): void
    {
        $this->getJson(route('api.v1.internal.ui.incidents.analytics'))
            ->assertUnauthorized();
    }

    public function test_verified_user_receives_a_scoped_incident_analytics_projection(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $otherMonitoring = Monitoring::factory()->for($otherUser)->create(['name' => 'Internal API']);
        $group = MonitoringGroup::factory()->for($user)->create(['name' => 'Customer services']);
        $group->monitorings()->attach($monitoring);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Customer status',
            'slug' => 'customer-status',
        ]);
        $statusPage->components()->create(['name' => 'Checkout', 'position' => 0])
            ->monitorings()->attach($monitoring, ['position' => 0]);

        $visibleIncident = $this->incidentFor($monitoring, [
            'affected_service' => 'Checkout',
            'incident_type' => IncidentType::AVAILABILITY,
            'severity' => IncidentSeverity::HIGH,
            'customer_impact' => IncidentCustomerImpact::OUTAGE,
            'up_at' => now()->subMinutes(10),
        ]);
        $this->incidentFor($otherMonitoring, ['affected_service' => 'Internal API']);

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.incidents.analytics'));

        $testResponse
            ->assertOk()
            ->assertJsonPath('data.groups.0.id', $group->id)
            ->assertJsonPath('data.status_pages.0.id', $statusPage->id)
            ->assertJsonPath('data.incidents.0.id', $visibleIncident->id)
            ->assertJsonPath('data.incidents.0.monitoring_name', 'Checkout API')
            ->assertJsonPath('data.incidents.0.status', 'resolved')
            ->assertJsonPath('data.distributions.by_type.0.key', IncidentType::AVAILABILITY->value)
            ->assertJsonMissing(['monitoring_name' => 'Internal API'])
            ->assertJsonStructure([
                'data' => [
                    'overview' => ['overall_state', 'summary'],
                    'filters',
                    'filter_options' => ['incident_types', 'severities', 'customer_impacts'],
                    'metrics' => ['total', 'resolved', 'open', 'mttr_minutes'],
                    'trend' => ['points', 'max'],
                    'distributions' => ['by_type', 'by_severity', 'by_impact'],
                ],
                'meta' => ['as_of', 'incident_pagination'],
            ])
            ->assertHeader('Cache-Control')
            ->assertHeader('ETag');

        $this->assertInternalUiTelemetry($testResponse, 60, 131072);
    }

    public function test_incident_analytics_filters_and_paginates_results(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 20]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create();

        foreach (range(1, 11) as $index) {
            $this->incidentFor($monitoring, [
                'affected_service' => 'Checkout API',
                'incident_type' => $index === 11 ? IncidentType::PERFORMANCE : IncidentType::AVAILABILITY,
                'down_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.incidents.analytics', [
            'incident_type' => IncidentType::AVAILABILITY->value,
            'affected_service' => 'Checkout',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('data.filters.incident_type', IncidentType::AVAILABILITY->value)
            ->assertJsonPath('data.metrics.total', 10)
            ->assertJsonPath('meta.incident_pagination.current_page', 2)
            ->assertJsonPath('meta.incident_pagination.total', 10)
            ->assertJsonCount(0, 'data.incidents');
    }

    public function test_incident_analytics_validates_filters_and_supports_conditional_requests(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create();
        $this->incidentFor($monitoring);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.incidents.analytics', ['days' => 7, 'page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['days', 'page']);

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.incidents.analytics'));
        $etag = (string) $testResponse->headers->get('ETag');

        $this->assertStringContainsString('private', (string) $testResponse->headers->get('Cache-Control'));
        $this->assertNotSame('', $etag);

        $this->actingAs($user)
            ->withHeader('If-None-Match', $etag)
            ->getJson(route('api.v1.internal.ui.incidents.analytics'))
            ->assertNotModified()
            ->assertHeader('ETag', $etag);
    }

    public function test_unverified_user_cannot_read_incident_analytics(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->unverified()->create(['package_id' => $package->id]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.incidents.analytics'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function incidentFor(Monitoring $monitoring, array $attributes = []): Incident
    {
        return Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => now()->subHour(),
            ...$attributes,
        ]);
    }
}
