<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use App\Support\HttpStatusCodeRanges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringExpectedHttpStatusesTest extends TestCase
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

    public function test_it_persists_normalized_expected_http_statuses_when_creating_a_monitoring(): void
    {
        $payload = $this->httpPayload([
            'name' => 'Redirecting API',
            'expected_http_statuses' => '200 - 299, 301,302',
        ]);

        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $payload);

        $testResponse->assertRedirect(route('monitorings.index'));

        $this->assertDatabaseHas('monitorings', [
            'name' => 'Redirecting API',
            'expected_http_statuses' => '200-299,301,302',
        ]);
    }

    public function test_it_defaults_expected_http_statuses_for_http_monitoring(): void
    {
        $payload = $this->httpPayload([
            'expected_http_statuses' => '',
        ]);

        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $payload);

        $testResponse->assertRedirect(route('monitorings.index'));

        $this->assertDatabaseHas('monitorings', [
            'name' => 'HTTP Monitoring',
            'expected_http_statuses' => HttpStatusCodeRanges::DEFAULT,
        ]);
    }

    public function test_it_updates_expected_http_statuses(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Existing HTTP API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com/health',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->serverInstance->code,
            'expected_http_statuses' => '200-299',
            'timeout' => 10,
            'http_method' => 'get',
        ]);

        $payload = $this->httpPayload([
            'name' => $monitoring->name,
            'status' => $monitoring->status->value,
            'preferred_location' => $monitoring->preferred_location,
            'timeout' => 12,
            'expected_http_statuses' => '200, 204, 300 - 399',
        ]);
        unset($payload['target']);

        $testResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), $payload);

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();

        $this->assertSame('200,204,300-399', $monitoring->expected_http_statuses);
    }

    public function test_it_rejects_invalid_expected_http_statuses(): void
    {
        $payload = $this->httpPayload([
            'expected_http_statuses' => '200-99',
        ]);

        $testResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), $payload);

        $testResponse->assertRedirect(route('monitorings.create'));
        $testResponse->assertSessionHasErrors(['expected_http_statuses']);

        $this->assertDatabaseCount('monitorings', 0);
    }

    public function test_non_http_monitoring_does_not_keep_expected_http_statuses(): void
    {
        $payload = [
            'name' => 'Ping Monitor',
            'type' => MonitoringType::PING->value,
            'target' => '8.8.8.8',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
        ];

        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $payload);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Ping Monitor')->firstOrFail();

        $this->assertNull($monitoring->expected_http_statuses);
    }

    public function test_internal_monitoring_list_includes_expected_http_statuses(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com/health',
            'preferred_location' => $this->serverInstance->code,
            'expected_http_statuses' => '200-399',
        ]);

        $testResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->serverInstance->code]));

        $testResponse->assertOk();
        $testResponse->assertJsonFragment([
            'id' => $monitoring->id,
            'expected_http_statuses' => '200-399',
        ]);
    }

    public function test_expected_http_statuses_are_rendered_in_form_but_not_as_detail_card(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com/health',
            'preferred_location' => $this->serverInstance->code,
            'expected_http_statuses' => '200-399',
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.form.expected_http_statuses'));
        $testResponse->assertSee('200-399');

        $detailResponse = $this->actingAs($this->user)->get(route('monitorings.show', $monitoring));

        $detailResponse->assertOk();
        $detailResponse->assertDontSeeText('HTTP Expectations');
        $detailResponse->assertDontSeeText('HTTP-Erwartungen');
        $detailResponse->assertDontSeeText('Acceptable status codes');
        $detailResponse->assertDontSeeText('Akzeptierte Statuscodes');
    }

    public function test_expected_http_status_range_matching_supports_codes_and_ranges(): void
    {
        $this->assertTrue(HttpStatusCodeRanges::contains('200-299,301,302', 204));
        $this->assertTrue(HttpStatusCodeRanges::contains('200-299,301,302', 301));
        $this->assertFalse(HttpStatusCodeRanges::contains('200-299,301,302', 404));
    }

    private function httpPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'HTTP Monitoring',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => HttpStatusCodeRanges::DEFAULT,
            'http_headers' => null,
            'preferred_location' => $this->serverInstance->code,
        ], $overrides);
    }
}
