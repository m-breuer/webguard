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

class MonitoringHttpHeadersTest extends TestCase
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

    public function test_it_persists_http_headers_from_json_when_creating_a_monitoring(): void
    {
        $payload = $this->httpPayload([
            'name' => 'HTTP API',
            'target' => 'https://example.com/health',
            'http_headers' => '{"Authorization":"Bearer token","X-Trace":"abc-123"}',
        ]);

        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $payload);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'HTTP API')->firstOrFail();

        $this->assertSame([
            'Authorization' => 'Bearer token',
            'X-Trace' => 'abc-123',
        ], $monitoring->http_headers);
    }

    public function test_it_persists_http_headers_when_updating_a_monitoring(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Existing HTTP API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com/health',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->serverInstance->code,
            'timeout' => 10,
            'http_method' => 'get',
            'http_headers' => ['Authorization' => 'Bearer old-token'],
        ]);

        $payload = $this->httpPayload([
            'name' => $monitoring->name,
            'status' => $monitoring->status->value,
            'preferred_location' => $monitoring->preferred_location,
            'timeout' => 12,
            'http_headers' => "{\n    \"Authorization\": \"Bearer fresh-token\",\n    \"X-Env\": \"production\"\n}",
        ]);
        unset($payload['target']);

        $testResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), $payload);

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();

        $this->assertSame([
            'Authorization' => 'Bearer fresh-token',
            'X-Env' => 'production',
        ], $monitoring->http_headers);
    }

    public function test_it_rejects_invalid_http_headers_json(): void
    {
        $payload = $this->httpPayload([
            'http_headers' => '{"Authorization":"Bearer token"',
        ]);

        $testResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), $payload);

        $testResponse->assertRedirect(route('monitorings.create'));
        $testResponse->assertSessionHasErrors(['http_headers']);

        $this->assertDatabaseCount('monitorings', 0);
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
            'http_headers' => null,
            'preferred_location' => $this->serverInstance->code,
        ], $overrides);
    }
}
