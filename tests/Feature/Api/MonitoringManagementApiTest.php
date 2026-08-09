<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\TeamRole;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitoringManagementApiTest extends TestCase
{
    public function test_user_can_manage_private_monitorings_through_api(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 5]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = $this->serverInstance('api-management-1');
        Sanctum::actingAs($user);

        $testResponse = $this->postJson('/api/v1/monitorings', $this->monitoringPayload([
            'name' => 'API Created Check',
            'target' => 'https://created.example.test',
            'preferred_locations' => [$serverInstance->code],
        ]));

        $testResponse->assertCreated();
        $monitoring = Monitoring::query()->where('name', 'API Created Check')->firstOrFail();
        $this->assertSame($user->id, $monitoring->user_id);

        $this->getJson('/api/v1/monitorings?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'API Created Check');

        $this->patchJson('/api/v1/monitorings/' . $monitoring->id, $this->monitoringPayload([
            'name' => 'API Updated Check',
            'target' => 'https://updated.example.test',
            'status' => MonitoringLifecycleStatus::PAUSED->value,
            'preferred_locations' => [$serverInstance->code],
        ]))->assertOk()
            ->assertJsonPath('data.name', 'API Updated Check')
            ->assertJsonPath('data.status', MonitoringLifecycleStatus::PAUSED->value)
            ->assertJsonPath('data.public_label_enabled', false);

        $this->patchJson('/api/v1/monitorings/' . $monitoring->id, $this->monitoringPayload([
            'name' => 'API Updated Check',
            'target' => 'https://updated.example.test',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_locations' => [$serverInstance->code],
        ]))->assertOk()
            ->assertJsonPath('data.status', MonitoringLifecycleStatus::ACTIVE->value);

        $this->deleteJson('/api/v1/monitorings/' . $monitoring->id)->assertNoContent();
        $this->assertSoftDeleted('monitorings', ['id' => $monitoring->id]);
        $this->deleteJson('/api/v1/monitorings/' . $monitoring->id)->assertNotFound();
    }

    public function test_monitoring_list_uses_a_stable_name_and_id_order(): void
    {
        Package::factory()->create(['monitoring_limit' => 5]);
        $user = User::factory()->create();
        $first = Monitoring::factory()->for($user)->create(['name' => 'Same name']);
        $second = Monitoring::factory()->for($user)->create(['name' => 'Same name']);
        Sanctum::actingAs($user);

        $testResponse = $this->getJson('/api/v1/monitorings?per_page=2');

        $testResponse->assertOk();
        $expectedIds = collect([$first->id, $second->id])->sort()->values()->all();
        $testResponse->assertJsonPath('data.0.id', $expectedIds[0]);
        $testResponse->assertJsonPath('data.1.id', $expectedIds[1]);
    }

    public function test_user_can_move_manageable_monitoring_between_private_and_team_ownership(): void
    {
        Package::factory()->create(['monitoring_limit' => 5]);
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $user->id]);
        TeamMembership::factory()->for($team)->for($user)->admin()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/monitorings/' . $monitoring->id . '/team-ownership', [
            'team_id' => $team->id,
        ])->assertOk()
            ->assertJsonPath('data.team_id', $team->id)
            ->assertJsonPath('data.user_id', null);

        $this->deleteJson('/api/v1/monitorings/' . $monitoring->id . '/team-ownership')
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.team_id', null);
    }

    public function test_store_rejects_private_monitoring_when_package_limit_is_reached(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 1]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = $this->serverInstance('api-management-limit');
        Monitoring::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/monitorings', $this->monitoringPayload([
            'name' => 'Over Limit',
            'preferred_locations' => [$serverInstance->code],
        ]))->assertUnprocessable()
            ->assertJsonPath('message', __('monitoring.messages.limit_reached'));
    }

    public function test_non_admin_cannot_move_monitoring_to_team(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $team = Team::factory()->create();
        TeamMembership::factory()->for($team)->for($user)->create(['role' => TeamRole::MEMBER]);
        $monitoring = Monitoring::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/monitorings/' . $monitoring->id . '/team-ownership', [
            'team_id' => $team->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_team_member_cannot_mutate_a_visible_team_monitoring(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        TeamMembership::factory()->for($team)->for($admin)->admin()->create();
        TeamMembership::factory()->for($team)->for($member)->create(['role' => TeamRole::MEMBER]);
        $monitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $admin->id,
        ]);
        $serverInstance = $this->serverInstance('api-management-team');
        Sanctum::actingAs($member);

        $this->patchJson('/api/v1/monitorings/' . $monitoring->id, $this->monitoringPayload([
            'preferred_locations' => [$serverInstance->code],
        ]))->assertForbidden();

        $this->deleteJson('/api/v1/monitorings/' . $monitoring->id)->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function monitoringPayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'API Check',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://api.example.test',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => '200-299',
            'preferred_locations' => ['api-management-1'],
            'notification_on_failure' => true,
            'failure_confirmation_threshold' => 1,
            'ssl_expiry_warning_days' => 7,
        ], $overrides);
    }

    private function serverInstance(string $code): ServerInstance
    {
        return ServerInstance::query()->create([
            'code' => $code,
            'ip_address' => '192.0.2.55',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }
}
