<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ServerInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringLocationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_locations_page_is_publicly_available(): void
    {
        ServerInstance::query()->create([
            'code' => 'us-1',
            'ip_address' => '203.0.113.10',
            'api_key_hash' => '1234567890abcdef',
            'is_active' => true,
        ]);

        $testResponse = $this->get(route('monitoring-locations'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('US-1');
        $testResponse->assertSeeText('203.0.113.10');
    }

    public function test_monitoring_locations_page_lists_only_active_server_instances(): void
    {
        ServerInstance::query()->create([
            'code' => 'active-1',
            'ip_address' => '198.51.100.20',
            'api_key_hash' => '1234567890abcdef',
            'is_active' => true,
        ]);

        ServerInstance::query()->create([
            'code' => 'inactive-1',
            'ip_address' => '198.51.100.21',
            'api_key_hash' => '1234567890abcdef',
            'is_active' => false,
        ]);

        $testResponse = $this->get(route('monitoring-locations'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('ACTIVE-1');
        $testResponse->assertSeeText('198.51.100.20');
        $testResponse->assertDontSeeText('INACTIVE-1');
        $testResponse->assertDontSeeText('198.51.100.21');
    }

    public function test_monitoring_locations_page_is_included_in_sitemap(): void
    {
        $testResponse = $this->get(route('sitemap'));

        $testResponse->assertOk();
        $testResponse->assertSee(route('monitoring-locations'), false);
    }
}
