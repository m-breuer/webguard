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
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class DomainExpirationMonitoringTest extends TestCase
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

    public function test_it_creates_domain_expiration_monitoring(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'Domain Expiry',
            'type' => MonitoringType::DOMAIN_EXPIRATION->value,
            'target' => 'Example.COM',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $this->assertDatabaseHas('monitorings', [
            'user_id' => $this->user->id,
            'name' => 'Domain Expiry',
            'type' => MonitoringType::DOMAIN_EXPIRATION->value,
            'target' => 'example.com',
            'timeout' => 5,
            'http_method' => null,
            'port' => null,
            'keyword' => null,
        ]);
    }

    public function test_it_rejects_url_targets_for_domain_expiration_monitoring(): void
    {
        $testResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), [
                'name' => 'Domain Expiry',
                'type' => MonitoringType::DOMAIN_EXPIRATION->value,
                'target' => 'https://example.com',
                'status' => MonitoringLifecycleStatus::ACTIVE->value,
                'preferred_location' => $this->serverInstance->code,
            ]);

        $testResponse->assertRedirect(route('monitorings.create'));
        $testResponse->assertSessionHasErrors(['target']);
        $this->assertDatabaseCount('monitorings', 0);
    }

    public function test_domain_expiration_monitoring_detail_shows_domain_result_without_response_chart(): void
    {
        Date::setTestNow('2026-04-24 12:00:00');

        $monitoring = Monitoring::factory()
            ->domainExpiration()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
            ]);

        $monitoring->domainResult()->create([
            'expires_at' => Date::now()->addDays(45),
            'is_valid' => true,
            'registrar' => 'Example Registrar',
            'checked_at' => Date::now(),
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.detail.domain.heading'));
        $testResponse->assertSeeText('Example Registrar');
        $testResponse->assertDontSeeText(__('monitoring.detail.response_time.heading'));
        $testResponse->assertDontSeeHtml('id="performance-chart"');
    }

    public function test_domain_expiration_edit_form_explains_monitoring_notifications(): void
    {
        $monitoring = Monitoring::factory()
            ->domainExpiration()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
            ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.form.notification_on_failure'));
        $testResponse->assertSeeText(__('monitoring.form.notification_on_failure_enabled'));
    }

    public function test_internal_monitoring_list_exposes_domain_expiration_monitoring(): void
    {
        $monitoring = Monitoring::factory()
            ->domainExpiration()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
                'status' => MonitoringLifecycleStatus::ACTIVE,
            ]);

        $testResponse = $this->withHeaders($this->instanceHeaders())
            ->getJson(route('v1.internal.monitorings.list', [
                'location' => $this->serverInstance->code,
                'type' => MonitoringType::DOMAIN_EXPIRATION->value,
            ]));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('0.id', $monitoring->id);
        $testResponse->assertJsonPath('0.type', MonitoringType::DOMAIN_EXPIRATION->value);
        $testResponse->assertJsonPath('0.target', 'example.com');
    }

    public function test_internal_instance_can_store_domain_expiration_result_and_status_response(): void
    {
        Date::setTestNow('2026-04-24 12:00:00');

        $monitoring = Monitoring::factory()
            ->domainExpiration()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
            ]);

        $testResponse = $this->withHeaders($this->instanceHeaders())
            ->postJson(route('v1.internal.domain-results.store'), [
                'monitoring_id' => $monitoring->id,
                'is_valid' => true,
                'expires_at' => Date::now()->addDays(90)->toIso8601String(),
                'registrar' => 'Example Registrar',
                'checked_at' => Date::now()->toIso8601String(),
            ]);

        $testResponse->assertOk();
        $this->assertDatabaseHas('monitoring_domain_results', [
            'monitoring_id' => $monitoring->id,
            'is_valid' => true,
            'registrar' => 'Example Registrar',
        ]);

        $statusResponse = $this->withHeaders($this->instanceHeaders())
            ->postJson(route('v1.internal.monitoring-responses.store'), [
                'monitoring_id' => $monitoring->id,
                'status' => MonitoringStatus::UP->value,
                'http_status_code' => null,
                'response_time' => null,
            ]);

        $statusResponse->assertOk();
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP->value,
            'http_status_code' => null,
        ]);
    }

    public function test_internal_instance_cannot_store_domain_result_for_non_domain_monitoring(): void
    {
        $monitoring = Monitoring::factory()
            ->for($this->user)
            ->create([
                'type' => MonitoringType::HTTP,
                'preferred_location' => $this->serverInstance->code,
            ]);

        $testResponse = $this->withHeaders($this->instanceHeaders())
            ->postJson(route('v1.internal.domain-results.store'), [
                'monitoring_id' => $monitoring->id,
                'is_valid' => true,
                'expires_at' => Date::now()->addDays(90)->toIso8601String(),
                'registrar' => 'Example Registrar',
                'checked_at' => Date::now()->toIso8601String(),
            ]);

        $testResponse->assertUnprocessable();
        $testResponse->assertJsonValidationErrors(['monitoring_id']);
        $this->assertDatabaseMissing('monitoring_domain_results', [
            'monitoring_id' => $monitoring->id,
        ]);
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
