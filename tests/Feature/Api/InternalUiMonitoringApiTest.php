<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

class InternalUiMonitoringApiTest extends TestCase
{
    use AssertsApiContracts;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_verified_user_can_list_only_visible_monitorings_with_a_bounded_page(): void
    {
        $user = User::factory()->create();
        $visibleMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Visible API']);
        Monitoring::factory()->for($user)->create(['name' => 'Another API']);
        Monitoring::factory()->create(['name' => 'Hidden API']);

        MonitoringResponse::query()->create([
            'monitoring_id' => $visibleMonitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 128.4,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.index', [
            'per_page' => 1,
            'search' => 'Visible',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $visibleMonitoring->id)
            ->assertJsonPath('data.0.latest_check.status', MonitoringStatus::UP->value)
            ->assertJsonPath('data.0.latest_check.response_time_ms', 128.4)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'target',
                    'type',
                    'lifecycle_status',
                    'groups',
                    'latest_check',
                    'open_incident',
                    'maintenance',
                ]],
                'meta' => ['as_of'],
            ])
            ->assertJsonMissing(['name' => 'Hidden API']);

        $this->assertInternalUiTelemetry(
            $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.index')),
            10,
            131072,
        );
    }

    public function test_guest_cannot_read_the_internal_ui_monitoring_projections(): void
    {
        $monitoring = Monitoring::factory()->create();

        $this->getJson(route('api.v1.internal.ui.monitorings.index'))
            ->assertUnauthorized();
        $this->getJson(route('api.v1.internal.ui.monitorings.show', $monitoring))
            ->assertUnauthorized();
        $this->getJson(route('api.v1.internal.ui.monitorings.cards', ['ids' => [$monitoring->id]]))
            ->assertUnauthorized();
        $this->getJson(route('api.v1.internal.ui.monitorings.form-options'))
            ->assertUnauthorized();
        $this->postJson(route('api.v1.internal.ui.monitorings.store'), [])
            ->assertUnauthorized();
    }

    public function test_internal_ui_monitoring_detail_returns_not_found_for_another_users_monitoring(): void
    {
        $user = User::factory()->create();
        $foreignMonitoring = Monitoring::factory()->create();

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.show', $foreignMonitoring))
            ->assertNotFound();
    }

    public function test_internal_ui_monitoring_cards_are_scoped_and_require_a_verified_session(): void
    {
        $user = User::factory()->create();
        $visibleMonitoring = Monitoring::factory()->for($user)->create();
        $foreignMonitoring = Monitoring::factory()->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $visibleMonitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 128.4,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.cards', [
            'ids' => [$visibleMonitoring->id, $foreignMonitoring->id],
        ]))
            ->assertOk()
            ->assertJsonPath('data.' . $visibleMonitoring->id . '.status', MonitoringStatus::UP->value)
            ->assertJsonMissingPath('data.' . $foreignMonitoring->id);

        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)->getJson(route('api.v1.internal.ui.monitorings.index'))
            ->assertForbidden();
    }

    public function test_internal_ui_monitoring_projections_validate_bounded_queries_and_return_a_scoped_detail(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Scoped detail API']);

        $this->actingAs($user)
            ->getJson(route('api.v1.internal.ui.monitorings.index', ['per_page' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
        $this->actingAs($user)
            ->getJson(route('api.v1.internal.ui.monitorings.cards'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');
        $this->actingAs($user)
            ->getJson(route('api.v1.internal.ui.monitorings.cards', ['ids' => array_fill(0, 101, $monitoring->id)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.show', $monitoring));

        $this->assertDataEnvelope($testResponse, ['data.id', 'data.name']);
        $testResponse->assertJsonPath('data.id', $monitoring->id)
            ->assertJsonPath('data.name', 'Scoped detail API')
            ->assertJsonStructure(['data' => ['initial_results_wait_minutes']]);
        $this->assertInternalUiTelemetry($testResponse, 15, 131072);
    }

    public function test_internal_ui_monitoring_cards_scope_heatmap_data_to_the_authenticated_user(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedMonitoring = Monitoring::factory()->for($user)->create();
        $foreignMonitoring = Monitoring::factory()->for($otherUser)->create();

        $foreignCheckedAt = Date::now()->subMinutes(5);
        MonitoringResponse::query()->create([
            'monitoring_id' => $foreignMonitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 500,
            'response_time' => 321.0,
            'created_at' => $foreignCheckedAt,
            'updated_at' => $foreignCheckedAt,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.cards', [
            'ids' => [$ownedMonitoring->id, $foreignMonitoring->id],
        ]))
            ->assertOk()
            ->assertJsonPath('data.' . $ownedMonitoring->id . '.heatmap.0.uptime', 0)
            ->assertJsonMissingPath('data.' . $foreignMonitoring->id);
    }

    public function test_internal_ui_monitoring_cards_accept_a_full_page_of_requested_ids(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 50]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $monitorings = Monitoring::factory()->count(26)->for($user)->create();

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.cards', [
            'ids' => $monitorings->pluck('id')->all(),
        ]))
            ->assertOk()
            ->assertJsonCount(26, 'data')
            ->assertJsonPath('data.' . $monitorings->first()->id . '.heatmap.0.uptime', 0);
    }

    public function test_internal_ui_monitoring_cards_batch_query_count_stays_bounded(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $user = User::factory()->create();
        $monitorings = Monitoring::factory()->count(3)->for($user)->create();

        foreach ($monitorings as $index => $monitoring) {
            foreach (range(0, 2) as $hourOffset) {
                $checkedAt = Date::now()->subHours($hourOffset)->subMinutes($index + 1);

                MonitoringResponse::query()->create([
                    'monitoring_id' => $monitoring->id,
                    'status' => $index === 1 ? MonitoringStatus::DOWN : MonitoringStatus::UP,
                    'http_status_code' => $index === 1 ? 503 : 200,
                    'response_time' => 120.0 + $index,
                    'created_at' => $checkedAt,
                    'updated_at' => $checkedAt,
                ]);
            }
        }

        Incident::query()->create([
            'monitoring_id' => $monitorings[1]->id,
            'down_at' => Date::now()->subHours(2),
            'up_at' => null,
            'created_at' => Date::now()->subHours(2),
            'updated_at' => Date::now()->subHours(2),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($monitorings as $monitoring) {
            $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/status')->assertOk();
            $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/heatmap')->assertOk();
        }

        $unbatchedSelectCount = $this->selectQueryCount();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.cards', [
            'ids' => $monitorings->pluck('id')->all(),
        ]));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('data.' . $monitorings[0]->id . '.status', MonitoringStatus::UP->value);
        $testResponse->assertJsonPath('data.' . $monitorings[1]->id . '.status', MonitoringStatus::DOWN->value);
        $testResponse->assertJsonCount(24, 'data.' . $monitorings[2]->id . '.heatmap');

        $batchedSelectCount = $this->selectQueryCount();

        $this->assertGreaterThan($batchedSelectCount, $unbatchedSelectCount);
        $this->assertLessThanOrEqual(4, $batchedSelectCount, (string) collect(DB::getQueryLog())->pluck('query')->implode(PHP_EOL));
    }

    public function test_verified_user_can_manage_monitorings_without_leaking_configuration_secrets(): void
    {
        $user = User::factory()->create();
        $location = ServerInstance::query()->create([
            'code' => 'ui-management-1',
            'ip_address' => '192.0.2.101',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.form-options'))
            ->assertOk()
            ->assertSee($location->code)
            ->assertJsonFragment(['http']);

        $payload = [
            'name' => 'First-party API check',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://monitoring.example.test',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'preferred_locations' => [$location->code],
            'notification_on_failure' => true,
            'failure_confirmation_threshold' => 2,
            'ssl_expiry_warning_days' => 7,
        ];

        $created = $this->actingAs($user)->postJson(route('api.v1.internal.ui.monitorings.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'First-party API check')
            ->assertJsonMissingPath('data.auth_password');
        $monitoringId = $created->json('data.id');

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.form-options.edit', $monitoringId))
            ->assertOk()
            ->assertJsonPath('data.monitoring.name', 'First-party API check')
            ->assertJsonMissingPath('data.monitoring.auth_password')
            ->assertJsonMissingPath('data.monitoring.http_headers');

        $this->actingAs($user)->patchJson(route('api.v1.internal.ui.monitorings.update', $monitoringId), [
            ...$payload,
            'name' => 'Updated first-party API check',
            'status' => MonitoringLifecycleStatus::PAUSED->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated first-party API check');

        $this->actingAs($user)->deleteJson(route('api.v1.internal.ui.monitorings.destroy', $monitoringId))
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
    }

    private function selectQueryCount(): int
    {
        return collect(DB::getQueryLog())
            ->filter(static fn (array $entry): bool => str_starts_with(mb_strtolower($entry['query']), 'select'))
            ->count();
    }
}
