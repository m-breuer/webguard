<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\IncidentFollowUpStatus;
use App\Enums\IncidentSeverity;
use App\Models\Incident;
use App\Models\IncidentFollowUp;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class IncidentWorkflowTest extends TestCase
{
    public function test_owner_can_manage_private_metadata_follow_ups_and_timeline_without_public_disclosure(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');
        ['user' => $user, 'statusPage' => $statusPage, 'incident' => $incident] = $this->incidentWorkspace();

        $this->actingAs($user)->patch(
            route('status-pages.incident-metadata.update', [$statusPage, $incident]),
            [
                'incident_type' => 'dependency',
                'severity' => 'high',
                'affected_service' => 'Checkout API',
                'customer_impact' => 'degraded',
                'contributing_category' => 'dependency',
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->actingAs($user)->post(
            route('status-pages.incident-follow-ups.store', [$statusPage, $incident]),
            [
                'title' => 'Add dependency timeout coverage',
                'description' => 'Cover the dependency failure mode in integration tests.',
                'assigned_user_id' => $user->id,
                'due_at' => '2026-07-22',
                'external_url' => 'https://github.com/marcel-breuer/webguard/issues/999',
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $incidentFollowUp = IncidentFollowUp::query()->firstOrFail();
        $incident->followUps()->create([
            'title' => 'Completed historical task',
            'status' => IncidentFollowUpStatus::COMPLETED,
            'completed_at' => Date::now()->subDay(),
        ]);

        $this->actingAs($user)->get(route('status-pages.show', [
            $statusPage,
            'follow_up_status' => IncidentFollowUpStatus::OPEN->value,
            'follow_up_assignee' => $user->id,
        ]))
            ->assertOk()
            ->assertSeeHtml('Add dependency timeout coverage')
            ->assertDontSeeHtml('Completed historical task');

        $this->actingAs($user)->post(
            route('status-pages.incident-timeline.store', [$statusPage, $incident]),
            [
                'title' => 'Dependency recovered',
                'description' => 'The upstream provider recovered after the failover.',
                'occurred_at' => '2026-07-15 11:30:00',
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'incident_type' => 'dependency',
            'severity' => 'high',
            'affected_service' => 'Checkout API',
            'customer_impact' => 'degraded',
            'contributing_category' => 'dependency',
        ]);
        $this->assertDatabaseHas('incident_follow_ups', [
            'id' => $incidentFollowUp->id,
            'assigned_user_id' => $user->id,
            'status' => IncidentFollowUpStatus::OPEN->value,
        ]);
        $this->assertDatabaseHas('incident_timeline_events', [
            'incident_id' => $incident->id,
            'title' => 'Dependency recovered',
            'source_type' => 'custom',
        ]);

        $this->actingAs($user)->get(route('status-pages.show', $statusPage))
            ->assertOk()
            ->assertSee(__('status_page.incident_workbench.heading'))
            ->assertSee(__('status_page.incident_workbench.public_updates'))
            ->assertSeeHtml('Checkout API')
            ->assertSeeHtml('Add dependency timeout coverage')
            ->assertSeeHtml('Dependency recovered')
            ->assertSeeHtml('Incident opened');

        $this->get(route('public-status-pages.show', $statusPage))
            ->assertOk()
            ->assertDontSeeText('Checkout API')
            ->assertDontSeeText('Add dependency timeout coverage')
            ->assertDontSeeText('Dependency recovered');
    }

    public function test_owner_can_complete_reopen_and_delete_follow_ups_and_edit_timeline_events(): void
    {
        ['user' => $user, 'statusPage' => $statusPage, 'incident' => $incident] = $this->incidentWorkspace();
        $incidentFollowUp = $incident->followUps()->create([
            'title' => 'Review timeout policy',
            'status' => IncidentFollowUpStatus::OPEN,
        ]);
        $incidentTimelineEvent = $incident->timelineEvents()->create([
            'title' => 'Initial mitigation',
            'description' => 'Restarted the worker.',
            'occurred_at' => now()->subMinutes(10),
            'source_type' => 'custom',
        ]);

        $this->actingAs($user)->patch(
            route('status-pages.incident-follow-ups.update', [$statusPage, $incident, $incidentFollowUp]),
            [
                'title' => 'Review timeout policy',
                'status' => IncidentFollowUpStatus::COMPLETED->value,
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->assertDatabaseHas('incident_follow_ups', [
            'id' => $incidentFollowUp->id,
            'status' => IncidentFollowUpStatus::COMPLETED->value,
        ]);
        $this->assertNotNull($incidentFollowUp->refresh()->completed_at);

        $this->actingAs($user)->patch(
            route('status-pages.incident-follow-ups.update', [$statusPage, $incident, $incidentFollowUp]),
            [
                'title' => 'Review timeout policy',
                'status' => IncidentFollowUpStatus::IN_PROGRESS->value,
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->assertDatabaseHas('incident_follow_ups', [
            'id' => $incidentFollowUp->id,
            'status' => IncidentFollowUpStatus::IN_PROGRESS->value,
            'completed_at' => null,
        ]);

        $this->actingAs($user)->patch(
            route('status-pages.incident-timeline.update', [$statusPage, $incident, $incidentTimelineEvent]),
            [
                'title' => 'Worker restarted',
                'description' => 'Restarted the affected worker and verified recovery.',
                'occurred_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->assertDatabaseHas('incident_timeline_events', [
            'id' => $incidentTimelineEvent->id,
            'title' => 'Worker restarted',
        ]);

        $this->actingAs($user)->delete(
            route('status-pages.incident-follow-ups.destroy', [$statusPage, $incident, $incidentFollowUp])
        )->assertRedirect(route('status-pages.show', $statusPage));
        $this->actingAs($user)->delete(
            route('status-pages.incident-timeline.destroy', [$statusPage, $incident, $incidentTimelineEvent])
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->assertDatabaseMissing('incident_follow_ups', ['id' => $incidentFollowUp->id]);
        $this->assertDatabaseMissing('incident_timeline_events', ['id' => $incidentTimelineEvent->id]);
    }

    public function test_incident_workspace_rejects_incidents_outside_status_page(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $includedMonitoring = Monitoring::factory()->for($user)->create();
        $outsideMonitoring = Monitoring::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPage->components()->create(['name' => 'API', 'position' => 0])
            ->monitorings()->attach($includedMonitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $outsideMonitoring->id,
            'down_at' => now()->subMinutes(20),
        ]);

        $this->actingAs($user)->patch(
            route('status-pages.incident-metadata.update', [$statusPage, $incident]),
            ['severity' => IncidentSeverity::CRITICAL->value]
        )->assertNotFound();

        $this->actingAs($user)->post(
            route('status-pages.incident-follow-ups.store', [$statusPage, $incident]),
            ['title' => 'Must not be saved']
        )->assertNotFound();

        $this->assertDatabaseMissing('incidents', [
            'id' => $incident->id,
            'severity' => IncidentSeverity::CRITICAL->value,
        ]);
        $this->assertDatabaseCount('incident_follow_ups', 0);
    }

    public function test_incident_analytics_filters_and_calculates_average_recovery_time(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $otherMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Search API']);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'incident_type' => 'dependency',
            'severity' => IncidentSeverity::HIGH,
            'affected_service' => 'Checkout API',
            'customer_impact' => 'degraded',
            'down_at' => Date::now()->subHour(),
            'up_at' => Date::now()->subMinutes(30),
        ]);
        Incident::query()->create([
            'monitoring_id' => $otherMonitoring->id,
            'incident_type' => 'availability',
            'severity' => IncidentSeverity::LOW,
            'affected_service' => 'Search API',
            'customer_impact' => 'outage',
            'down_at' => Date::now()->subMinutes(10),
        ]);

        $this->actingAs($user)->get(route('incidents.analytics', [
            'days' => 90,
            'severity' => IncidentSeverity::HIGH->value,
        ]))
            ->assertOk()
            ->assertSeeText('Average MTTR')
            ->assertSeeText('30 min')
            ->assertSeeText('Checkout API')
            ->assertDontSeeText('Search API');
    }

    public function test_incident_analytics_only_includes_incidents_for_visible_monitorings(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $otherMonitoring = Monitoring::factory()->for($otherUser)->create(['name' => 'Other API']);

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'affected_service' => 'Checkout API',
            'down_at' => Date::now()->subHour(),
        ]);
        Incident::query()->create([
            'monitoring_id' => $otherMonitoring->id,
            'down_at' => Date::now()->subMinutes(30),
        ]);

        $this->actingAs($user)->get(route('incidents.analytics'))
            ->assertOk()
            ->assertSeeText('Checkout API')
            ->assertDontSeeText('Other API');
    }

    /**
     * @return array{user: User, statusPage: StatusPage, incident: Incident}
     */
    private function incidentWorkspace(): array
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPage->components()->create(['name' => 'API', 'position' => 0])
            ->monitorings()->attach($monitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => now()->subMinutes(30),
            'up_at' => now()->subMinutes(5),
        ]);

        return compact('user', 'statusPage', 'incident');
    }
}
