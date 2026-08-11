<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTypeFieldVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_hides_server_health_and_dns_fields_until_their_type_is_selected(): void
    {
        $user = $this->createUserWithServerInstance();

        $testResponse = $this->actingAs($user)->get(route('monitorings.create'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-monitoring-current-type="http"');
        $testResponse->assertSeeHtml('data-monitoring-type-fields="server_health" hidden');
        $testResponse->assertSeeHtml('data-monitoring-type-fields="dns_record" hidden');
    }

    public function test_edit_form_uses_the_monitoring_type_to_set_initial_field_visibility(): void
    {
        $user = $this->createUserWithServerInstance();
        $serverHealthMonitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::SERVER_HEALTH,
        ]);
        $dnsMonitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::DNS_RECORD,
        ]);

        $this->actingAs($user)->get(route('monitorings.edit', $serverHealthMonitoring))
            ->assertOk()
            ->assertSeeHtml('data-monitoring-current-type="server_health"')
            ->assertSeeHtml('data-monitoring-type-fields="dns_record" hidden')
            ->assertDontSeeHtml('data-monitoring-type-fields="server_health" hidden');

        $this->actingAs($user)->get(route('monitorings.edit', $dnsMonitoring))
            ->assertOk()
            ->assertSeeHtml('data-monitoring-current-type="dns_record"')
            ->assertSeeHtml('data-monitoring-type-fields="server_health" hidden')
            ->assertDontSeeHtml('data-monitoring-type-fields="dns_record" hidden');
    }

    public function test_check_configuration_is_hidden_when_the_monitoring_type_has_no_configuration_fields(): void
    {
        $user = $this->createUserWithServerInstance();
        $pingMonitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::PING,
        ]);

        $this->actingAs($user)->get(route('monitorings.create'))
            ->assertOk()
            ->assertSeeHtml('data-monitoring-check-configuration')
            ->assertSeeHtml('data-monitoring-type-fields="http keyword port heartbeat server_health dns_record"')
            ->assertDontSeeHtml('data-monitoring-check-configuration data-monitoring-type-fields="http keyword port heartbeat server_health dns_record" hidden');

        $this->actingAs($user)->get(route('monitorings.edit', $pingMonitoring))
            ->assertOk()
            ->assertSeeHtml('data-monitoring-check-configuration data-monitoring-type-fields="http keyword port heartbeat server_health dns_record" hidden');
    }

    private function createUserWithServerInstance(): User
    {
        ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );

        return User::factory()->create([
            'package_id' => Package::factory()->create()->id,
        ]);
    }
}
