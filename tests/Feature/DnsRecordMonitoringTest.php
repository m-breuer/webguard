<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class DnsRecordMonitoringTest extends TestCase
{
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

    public function test_it_creates_dns_record_monitoring_with_normalized_expectations(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'DNS Watch',
            'type' => MonitoringType::DNS_RECORD->value,
            'target' => 'Example.COM',
            'dns_record_type' => 'a',
            'dns_expected_values' => "192.0.2.11\n192.0.2.10\n192.0.2.10",
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'DNS Watch')->firstOrFail();

        $this->assertSame(MonitoringType::DNS_RECORD, $monitoring->type);
        $this->assertSame('example.com', $monitoring->target);
        $this->assertSame('A', $monitoring->dns_record_type);
        $this->assertSame(['192.0.2.10', '192.0.2.11'], $monitoring->dns_expected_values);
        $this->assertNull($monitoring->expected_http_statuses);
        $this->assertNull($monitoring->http_method);
        $this->assertNull($monitoring->port);
        $this->assertNull($monitoring->keyword);
    }

    public function test_it_rejects_invalid_dns_record_monitoring_targets_and_values(): void
    {
        $testResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), $this->dnsPayload([
                'target' => 'https://example.com',
            ]));

        $testResponse->assertRedirect(route('monitorings.create'));
        $testResponse->assertSessionHasErrors(['target']);

        $valueResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), $this->dnsPayload([
                'dns_record_type' => 'A',
                'dns_expected_values' => 'not-an-ip',
            ]));

        $valueResponse->assertRedirect(route('monitorings.create'));
        $valueResponse->assertSessionHasErrors(['dns_expected_values']);

        $this->assertDatabaseCount('monitorings', 0);
    }

    public function test_it_updates_dns_record_expectations(): void
    {
        $monitoring = Monitoring::factory()
            ->dnsRecord()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
            ]);

        $payload = $this->dnsPayload([
            'name' => 'DNS MX Watch',
            'type' => MonitoringType::DNS_RECORD->value,
            'status' => $monitoring->status->value,
            'preferred_location' => $monitoring->preferred_location,
            'dns_record_type' => 'MX',
            'dns_expected_values' => "20 Mail2.Example.COM.\n10 Mail1.Example.COM.",
        ]);
        unset($payload['target']);

        $testResponse = $this->actingAs($this->user)
            ->patch(route('monitorings.update', $monitoring), $payload);

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();

        $this->assertSame('example.com', $monitoring->target);
        $this->assertSame('MX', $monitoring->dns_record_type);
        $this->assertSame(['10 mail1.example.com', '20 mail2.example.com'], $monitoring->dns_expected_values);
    }

    public function test_internal_monitoring_list_exposes_dns_record_monitoring_configuration(): void
    {
        $monitoring = Monitoring::factory()
            ->dnsRecord()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
                'status' => MonitoringLifecycleStatus::ACTIVE,
                'dns_record_type' => 'TXT',
                'dns_expected_values' => ['v=spf1 include:example.com ~all'],
            ]);

        $testResponse = $this->withHeaders($this->instanceHeaders())
            ->getJson(route('v1.internal.monitorings.list', [
                'location' => $this->serverInstance->code,
                'type' => MonitoringType::DNS_RECORD->value,
            ]));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('0.id', $monitoring->id);
        $testResponse->assertJsonPath('0.type', MonitoringType::DNS_RECORD->value);
        $testResponse->assertJsonPath('0.target', 'example.com');
        $testResponse->assertJsonPath('0.dns_record_type', 'TXT');
        $testResponse->assertJsonPath('0.dns_expected_values.0', 'v=spf1 include:example.com ~all');
    }

    public function test_dns_record_monitoring_uses_normal_status_response_storage(): void
    {
        $monitoring = Monitoring::factory()
            ->dnsRecord()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
            ]);

        $testResponse = $this->withHeaders($this->instanceHeaders())
            ->postJson(route('v1.internal.monitoring-responses.store'), [
                'monitoring_id' => $monitoring->id,
                'status' => MonitoringStatus::DOWN->value,
                'response_time' => 24.7,
            ]);

        $testResponse->assertOk();
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => null,
        ]);
    }

    public function test_dns_record_expectations_are_rendered_on_detail_page(): void
    {
        $monitoring = Monitoring::factory()
            ->dnsRecord()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
                'dns_record_type' => 'A',
                'dns_expected_values' => ['192.0.2.10'],
            ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.detail.dns.heading'));
        $testResponse->assertSeeText('A');
        $testResponse->assertSeeText('192.0.2.10');
    }

    /**
     * @return array<string, mixed>
     */
    private function dnsPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'DNS Watch',
            'type' => MonitoringType::DNS_RECORD->value,
            'target' => 'example.com',
            'dns_record_type' => 'A',
            'dns_expected_values' => '192.0.2.10',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function instanceHeaders(): array
    {
        return [
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ];
    }
}
