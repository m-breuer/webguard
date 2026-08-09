<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\IncidentFollowUpStatus;
use App\Enums\IncidentUpdateStatus;
use App\Enums\TeamRole;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\StatusPageSubscription;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileStatusPageWorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_status_pages_and_change_publication_state(): void
    {
        ['user' => $user, 'statusPage' => $statusPage] = $this->workspace();
        $monitoringGroup = MonitoringGroup::factory()->for($user)->create(['name' => 'Customer-facing']);
        $monitoringGroup->monitorings()->attach($statusPage->components->first()->monitorings->first()->id);
        $statusPage->components->first()->update(['monitoring_group_id' => $monitoringGroup->id, 'source_type' => 'monitoring_group']);
        StatusPageSubscription::query()->create([
            'status_page_id' => $statusPage->id,
            'email' => 'subscriber@example.test',
            'unsubscribe_token' => 'subscriber-unsubscribe-token',
            'verified_at' => Date::now(),
        ]);
        $this->actingAsMobile($user);

        $this->getJson('/api/v1/mobile/status-pages')
            ->assertOk()
            ->assertJsonPath('data.0.id', $statusPage->id)
            ->assertJsonPath('data.0.publication.is_public', true)
            ->assertJsonPath('data.0.verified_subscriber_count', 1)
            ->assertJsonPath('data.0.open_incident_count', 1);

        $this->getJson('/api/v1/mobile/status-pages/' . $statusPage->id)
            ->assertOk()
            ->assertJsonPath('data.components.0.monitoring_group.id', $monitoringGroup->id)
            ->assertJsonPath('data.components.0.monitorings.0.id', $statusPage->components->first()->monitorings->first()->id);

        $this->patchJson('/api/v1/mobile/status-pages/' . $statusPage->id . '/publication', ['is_public' => false])
            ->assertOk()
            ->assertJsonPath('data.publication.is_public', false);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'status_page_unpublished',
            'causer_id' => $user->id,
        ]);
    }

    public function test_owner_can_publish_an_idempotent_incident_update_and_manage_incident_workspace(): void
    {
        ['user' => $user, 'statusPage' => $statusPage, 'incident' => $incident] = $this->workspace();
        $this->actingAsMobile($user);
        $base = '/api/v1/mobile/status-pages/' . $statusPage->id . '/incidents/' . $incident->id;

        $this->getJson($base)
            ->assertOk()
            ->assertJsonPath('data.readiness.requires_public_update', true)
            ->assertJsonPath('data.lifecycle.state', 'open');

        $this->withHeaders(['Idempotency-Key' => 'incident-update-001'])
            ->postJson($base . '/updates', [
                'status' => IncidentUpdateStatus::IDENTIFIED->value,
                'message' => 'The upstream dependency is being restored.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.updates.0.message', 'The upstream dependency is being restored.')
            ->assertJsonPath('data.readiness.requires_public_update', false);

        $this->withHeaders(['Idempotency-Key' => 'incident-update-001'])
            ->postJson($base . '/updates', [
                'status' => IncidentUpdateStatus::IDENTIFIED->value,
                'message' => 'The upstream dependency is being restored.',
            ])
            ->assertOk();
        $this->assertDatabaseCount('incident_updates', 1);

        $this->patchJson($base . '/metadata', [
            'severity' => 'high',
            'affected_service' => 'Checkout API',
        ])->assertOk()
            ->assertJsonPath('data.metadata.severity', 'high');
        $this->patchJson($base . '/review', [
            'problem_description' => 'An upstream dependency was unavailable.',
        ])->assertOk()
            ->assertJsonPath('data.metadata.problem_description', 'An upstream dependency was unavailable.');

        $this->withHeaders(['Idempotency-Key' => 'follow-up-001'])
            ->postJson($base . '/follow-ups', [
                'title' => 'Add a provider fallback',
                'assigned_user_id' => $user->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.follow_ups.0.title', 'Add a provider fallback');
        $this->withHeaders(['Idempotency-Key' => 'follow-up-001'])
            ->postJson($base . '/follow-ups', [
                'title' => 'Add a provider fallback',
                'assigned_user_id' => $user->id,
            ])
            ->assertOk();
        $this->assertDatabaseCount('incident_follow_ups', 1);

        $followUpId = $this->getJson($base)->json('data.follow_ups.0.id');
        $this->patchJson($base . '/follow-ups/' . $followUpId, [
            'title' => 'Add a provider fallback',
            'status' => IncidentFollowUpStatus::COMPLETED->value,
        ])->assertOk()
            ->assertJsonPath('data.follow_ups.0.status', IncidentFollowUpStatus::COMPLETED->value);

        $this->withHeaders(['Idempotency-Key' => 'timeline-001'])
            ->postJson($base . '/timeline', [
                'title' => 'Fallback activated',
                'occurred_at' => Date::now()->subMinutes(5)->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.timeline.1.title', 'Fallback activated');
        $this->withHeaders(['Idempotency-Key' => 'timeline-001'])
            ->postJson($base . '/timeline', [
                'title' => 'Fallback activated',
                'occurred_at' => Date::now()->subMinutes(5)->toIso8601String(),
            ])
            ->assertOk();
        $this->assertDatabaseCount('incident_timeline_events', 1);

        $timelineEventId = $this->getJson($base)->json('data.custom_timeline_events.0.id');
        $this->patchJson($base . '/timeline/' . $timelineEventId, [
            'title' => 'Fallback active',
            'occurred_at' => Date::now()->subMinutes(5)->toIso8601String(),
        ])->assertOk()
            ->assertJsonPath('data.custom_timeline_events.0.title', 'Fallback active');
        $this->deleteJson($base . '/timeline/' . $timelineEventId)->assertNoContent();
        $this->deleteJson($base . '/follow-ups/' . $followUpId)->assertNoContent();
        $this->assertDatabaseCount('incident_timeline_events', 0);
        $this->assertDatabaseCount('incident_follow_ups', 0);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'incident_update_published',
            'causer_id' => $user->id,
        ]);
        $this->get(route('public-status-pages.show', $statusPage))
            ->assertOk()
            ->assertSeeText('The upstream dependency is being restored.');
    }

    public function test_incident_communication_requires_status_page_ownership_and_monitoring_management(): void
    {
        ['user' => $user, 'statusPage' => $statusPage, 'incident' => $incident] = $this->workspace();
        $otherUser = User::factory()->create();
        $this->actingAsMobile($otherUser);

        $this->getJson('/api/v1/mobile/status-pages/' . $statusPage->id)->assertNotFound();

        $team = Team::factory()->create(['created_by_user_id' => $user->id]);
        TeamMembership::factory()->for($team)->for($user)->create(['role' => TeamRole::MEMBER]);
        $teamMonitoring = Monitoring::factory()->for($team)->create(['user_id' => null]);
        $teamStatusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Team Status',
            'is_public' => true,
        ]);
        $teamStatusPage->components()->create(['name' => 'Team API', 'position' => 0])
            ->monitorings()
            ->attach($teamMonitoring->id, ['position' => 0]);
        $teamIncident = Incident::query()->create([
            'monitoring_id' => $teamMonitoring->id,
            'down_at' => Date::now()->subMinutes(10),
        ]);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/mobile/status-pages/' . $teamStatusPage->id)
            ->assertOk();
        $this->getJson('/api/v1/mobile/status-pages/' . $teamStatusPage->id . '/incidents')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/mobile/status-pages/' . $teamStatusPage->id . '/incidents/' . $teamIncident->id)
            ->assertNotFound();
        $this->postJson('/api/v1/mobile/status-pages/' . $statusPage->id . '/incidents/' . $incident->id . '/updates', [
            'status' => IncidentUpdateStatus::INVESTIGATING->value,
            'message' => 'Missing idempotency key.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
    }

    /**
     * @return array{user: User, statusPage: StatusPage, incident: Incident}
     */
    private function workspace(): array
    {
        Date::setTestNow('2026-08-09 20:00:00');
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'is_public' => true,
        ]);
        $statusPage->components()->create(['name' => 'API', 'position' => 0])
            ->monitorings()
            ->attach($monitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subMinutes(20),
        ]);

        return compact('user', 'statusPage', 'incident');
    }

    private function actingAsMobile(User $user): void
    {
        $this->withToken($user->createToken('ios-app: Test Device')->plainTextToken);
    }
}
