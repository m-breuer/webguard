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
        $this->assertTypeFieldsAreHidden($testResponse->getContent(), 'server_health');
        $this->assertTypeFieldsAreHidden($testResponse->getContent(), 'dns_record');
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

        $testResponse = $this->actingAs($user)->get(route('monitorings.edit', $serverHealthMonitoring));

        $testResponse
            ->assertOk()
            ->assertSeeHtml('data-monitoring-current-type="server_health"');
        $this->assertTypeFieldsAreHidden($testResponse->getContent(), 'dns_record');
        $this->assertTypeFieldsAreVisible($testResponse->getContent(), 'server_health');

        $dnsResponse = $this->actingAs($user)->get(route('monitorings.edit', $dnsMonitoring));

        $dnsResponse
            ->assertOk()
            ->assertSeeHtml('data-monitoring-current-type="dns_record"');
        $this->assertTypeFieldsAreHidden($dnsResponse->getContent(), 'server_health');
        $this->assertTypeFieldsAreVisible($dnsResponse->getContent(), 'dns_record');
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

        $testResponse = $this->actingAs($user)->get(route('monitorings.edit', $pingMonitoring));

        $testResponse
            ->assertOk()
            ->assertSeeHtml('data-monitoring-check-configuration');
        $this->assertMatchesRegularExpression(
            '/<details\\s+data-monitoring-check-configuration\\s+data-monitoring-type-fields="http keyword port heartbeat server_health dns_record"\\s+hidden\\b/s',
            $testResponse->getContent()
        );
    }

    private function assertTypeFieldsAreHidden(string $content, string $type): void
    {
        $this->assertMatchesRegularExpression(
            '/data-monitoring-type-fields="' . preg_quote($type, '/') . '"\\s+hidden\\b/',
            $content
        );
    }

    private function assertTypeFieldsAreVisible(string $content, string $type): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/data-monitoring-type-fields="' . preg_quote($type, '/') . '"\\s+hidden\\b/',
            $content
        );
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
