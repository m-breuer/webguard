<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ServerInstance;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class PublicMonitoringLocationApiTest extends TestCase
{
    public function test_an_unauthenticated_caller_can_list_active_public_monitoring_locations(): void
    {
        Date::setTestNow('2026-08-18 12:00:00');
        $this->beforeApplicationDestroyed(fn (): mixed => Date::setTestNow());

        $germany = $this->serverInstance('de-public-1', 'Germany', 'DE', 'Europe', '1.1.1.1');
        $unitedStates = $this->serverInstance('us-1', 'United States', 'US', 'North America', '8.8.8.8');
        $this->serverInstance('private-1', 'Private network', 'DE', 'Europe', '10.0.0.1');
        $this->serverInstance('inactive-1', 'Inactive', 'NL', 'Europe', '9.9.9.9', false);

        $this->getJson(route('v1.public.monitoring-locations.index'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=300, public, stale-while-revalidate=60')
            ->assertJsonPath('meta.version', '1')
            ->assertJsonPath('meta.generated_at', '2026-08-18T12:00:00+02:00')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', $germany->code)
            ->assertJsonPath('data.0.name', 'Germany')
            ->assertJsonPath('data.0.country_code', 'DE')
            ->assertJsonPath('data.0.region', 'Europe')
            ->assertJsonPath('data.0.allowlist_ips', ['1.1.1.1'])
            ->assertJsonPath('data.0.active', true)
            ->assertJsonPath('data.1.code', $unitedStates->code)
            ->assertJsonMissing(['api_key_hash' => $germany->api_key_hash])
            ->assertJsonMissing(['code' => 'private-1'])
            ->assertJsonMissing(['code' => 'inactive-1']);
    }

    public function test_location_output_tracks_the_canonical_server_instance_configuration(): void
    {
        $serverInstance = $this->serverInstance('nl-1', 'Amsterdam', 'NL', 'Europe', '1.0.0.1');

        $this->getJson(route('v1.public.monitoring-locations.index'))
            ->assertJsonPath('data.0.name', 'Amsterdam');

        $serverInstance->update([
            'display_name' => 'Amsterdam West',
            'ip_address' => '1.0.0.2',
        ]);

        $this->getJson(route('v1.public.monitoring-locations.index'))
            ->assertJsonPath('data.0.name', 'Amsterdam West')
            ->assertJsonPath('data.0.allowlist_ips', ['1.0.0.2']);
    }

    private function serverInstance(
        string $code,
        string $displayName,
        string $countryCode,
        string $region,
        string $ipAddress,
        bool $isActive = true,
    ): ServerInstance {
        return ServerInstance::query()->create([
            'code' => $code,
            'display_name' => $displayName,
            'country_code' => $countryCode,
            'region' => $region,
            'ip_address' => $ipAddress,
            'api_key_hash' => 'private-instance-key',
            'is_active' => $isActive,
        ]);
    }
}
