<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTargetEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ServerInstance $serverInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $this->user = User::factory()->create(['package_id' => $package->id]);

        $this->serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $this->serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }

    public function test_target_can_be_set_when_creating_monitoring(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $this->creationPayload());

        $testResponse->assertRedirect(route('monitorings.index'));
        $this->assertDatabaseHas('monitorings', [
            'user_id' => $this->user->id,
            'name' => 'Primary Ping',
            'target' => '8.8.8.8',
            'type' => MonitoringType::PING->value,
        ]);
    }

    public function test_edit_page_displays_target_as_editable(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::PING,
            'target' => '1.1.1.1',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('name="target"');
        $testResponse->assertSee('1.1.1.1');
        $testResponse->assertDontSee(__('monitoring.form.target_immutable_help'));
    }

    public function test_update_payload_can_change_target(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::PING,
            'target' => '1.1.1.1',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $payload = $this->updatePayload($monitoring, [
            'name' => 'Renamed Monitor',
            'target' => '9.9.9.9',
        ]);

        $testResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), $payload);

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));
        $monitoring->refresh();

        $this->assertSame('Renamed Monitor', $monitoring->name);
        $this->assertSame('9.9.9.9', $monitoring->target);
    }

    private function creationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Primary Ping',
            'type' => MonitoringType::PING->value,
            'target' => '8.8.8.8',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
        ], $overrides);
    }

    private function updatePayload(Monitoring $monitoring, array $overrides = []): array
    {
        return array_merge([
            'name' => $monitoring->name,
            'type' => $monitoring->type->value,
            'status' => $monitoring->status->value,
            'preferred_location' => $monitoring->preferred_location,
        ], $overrides);
    }
}
