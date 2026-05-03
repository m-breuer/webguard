<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class PublicStatusPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_public_status_page_shows_status_uptime_maintenance_and_incidents(): void
    {
        Date::setTestNow('2026-05-03 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'maintenance_from' => Date::now()->subHour(),
            'maintenance_until' => Date::now()->addHour(),
            'created_at' => Date::now()->subDays(100),
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ]);

        foreach ([2, 20, 60] as $daysAgo) {
            $this->createDailyResult($monitoring, Date::now()->subDays($daysAgo)->toDateString());
        }

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDays(3)->subMinutes(30),
            'up_at' => Date::now()->subDays(3),
        ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Primary API');
        $testResponse->assertSeeText(__('monitoring.public_label.current_status'));
        $testResponse->assertSeeText('UP');
        $testResponse->assertSeeText('HTTP 200');
        $testResponse->assertSeeText(__('monitoring.index.table.maintenance'));
        $testResponse->assertSeeText('Last 7 days');
        $testResponse->assertSeeText('Last 30 days');
        $testResponse->assertSeeText('Last 90 days');
        $testResponse->assertSeeText('100.00%');
        $testResponse->assertSeeText(__('monitoring.detail.incidents.heading'));
        $testResponse->assertSeeText(__('monitoring.public_label.resolved'));
    }

    public function test_public_status_page_returns_not_found_when_public_label_is_disabled(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertNotFound();
    }

    public function test_public_status_page_does_not_expose_private_monitoring_configuration(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'public_label_enabled' => true,
            'http_headers' => ['Authorization' => 'Bearer hidden-token'],
            'http_body' => '{"secret":"hidden-body"}',
            'auth_username' => 'hidden-user',
            'auth_password' => 'hidden-password',
        ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertDontSeeText('hidden-token');
        $testResponse->assertDontSeeText('hidden-body');
        $testResponse->assertDontSeeText('hidden-user');
        $testResponse->assertDontSeeText('hidden-password');
    }

    public function test_public_status_page_hides_heartbeat_ping_url(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()
            ->heartbeat()
            ->for($user)
            ->create([
                'target' => 'https://webguard.test/heartbeat/super-secret-token',
                'heartbeat_token' => 'super-secret-token',
                'public_label_enabled' => true,
            ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.public_label.private_target'));
        $testResponse->assertDontSeeText('super-secret-token');
        $testResponse->assertDontSeeText($monitoring->target);
    }

    private function createDailyResult(Monitoring $monitoring, string $date): void
    {
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => 1,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 100,
            'downtime_percentage' => 0,
            'unknown_percentage' => 0,
            'uptime_minutes' => 24 * 60,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => 123.4,
            'min_response_time' => 123.4,
            'max_response_time' => 123.4,
            'incidents_count' => 0,
        ]);
    }
}
