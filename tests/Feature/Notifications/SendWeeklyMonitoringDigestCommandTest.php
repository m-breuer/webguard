<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\UserRole;
use App\Mail\WeeklyMonitoringDigestMail;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringSslResult;
use App\Models\Package;
use App\Models\User;
use App\Services\WeeklyMonitoringDigestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendWeeklyMonitoringDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_sends_weekly_digest_with_uptime_incidents_downtime_and_expiry_warnings(): void
    {
        Date::setTestNow('2026-04-20 09:00:00');
        Package::factory()->create();

        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Storefront',
            'target' => 'https://example.com',
            'type' => MonitoringType::HTTP,
        ]);
        $domainMonitoring = Monitoring::factory()->domainExpiration()->for($user)->create([
            'name' => 'Primary domain',
            'target' => 'example.com',
        ]);

        for ($day = 13; $day <= 19; $day++) {
            MonitoringDailyResult::query()->create([
                'monitoring_id' => $monitoring->id,
                'date' => '2026-04-' . $day,
                'uptime_total' => 286,
                'downtime_total' => 2,
                'unknown_total' => 0,
                'uptime_percentage' => 99.31,
                'downtime_percentage' => 0.69,
                'unknown_percentage' => 0,
                'uptime_minutes' => 1430,
                'downtime_minutes' => 10,
                'unknown_minutes' => 0,
                'avg_response_time' => 120,
                'min_response_time' => 80,
                'max_response_time' => 250,
                'incidents_count' => 1,
            ]);
        }

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-15 10:00:00',
            'up_at' => '2026-04-15 10:30:00',
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-16 11:00:00',
            'up_at' => '2026-04-16 12:15:00',
        ]);

        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'expires_at' => '2026-05-01 00:00:00',
            'is_valid' => true,
            'issuer' => 'Example CA',
            'issued_at' => '2026-02-01 00:00:00',
        ]);
        MonitoringDomainResult::query()->create([
            'monitoring_id' => $domainMonitoring->id,
            'expires_at' => '2026-05-10 00:00:00',
            'is_valid' => true,
            'registrar' => 'Example Registrar',
            'checked_at' => '2026-04-20 00:00:00',
        ]);

        Mail::fake();

        Artisan::call('notifications:send-weekly-monitoring-digest', [
            '--period-end' => '2026-04-19',
        ]);

        Mail::assertSent(WeeklyMonitoringDigestMail::class, function (WeeklyMonitoringDigestMail $weeklyMonitoringDigestMail) use ($user): bool {
            $rendered = $weeklyMonitoringDigestMail->render();

            return $weeklyMonitoringDigestMail->hasTo($user->email)
                && $weeklyMonitoringDigestMail->digest['period_start']->toDateString() === '2026-04-13'
                && $weeklyMonitoringDigestMail->digest['period_end']->toDateString() === '2026-04-19'
                && round($weeklyMonitoringDigestMail->digest['overview']['uptime_percentage'], 2) === 99.31
                && $weeklyMonitoringDigestMail->digest['overview']['incidents_count'] === 2
                && $weeklyMonitoringDigestMail->digest['overview']['longest_downtime_minutes'] === 75
                && count($weeklyMonitoringDigestMail->digest['ssl_warnings']) === 1
                && count($weeklyMonitoringDigestMail->digest['domain_warnings']) === 1
                && str_contains($rendered, 'Storefront')
                && str_contains($rendered, '99.31%')
                && str_contains($rendered, 'SSL certificates')
                && str_contains($rendered, 'Domains');
        });
    }

    public function test_does_not_send_weekly_digest_to_guest_users_or_users_without_active_monitorings(): void
    {
        Date::setTestNow('2026-04-20 09:00:00');
        Package::factory()->create();

        $guestUser = User::factory()->create([
            'role' => UserRole::GUEST,
        ]);
        Monitoring::factory()->for($guestUser)->create();

        $pausedUser = User::factory()->create();
        Monitoring::factory()->for($pausedUser)->create([
            'status' => MonitoringLifecycleStatus::PAUSED,
        ]);

        Mail::fake();

        Artisan::call('notifications:send-weekly-monitoring-digest', [
            '--period-end' => '2026-04-19',
        ]);

        Mail::assertNothingSent();
    }

    public function test_weekly_digest_clips_incidents_to_the_requested_period(): void
    {
        Date::setTestNow('2026-04-20 09:00:00');
        Package::factory()->create();

        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Boundary Monitor',
            'target' => 'https://example.com',
            'type' => MonitoringType::HTTP,
        ]);

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-01 00:00:00',
            'up_at' => '2026-04-13 00:30:00',
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-19 22:00:00',
            'up_at' => '2026-04-25 00:00:00',
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-19 23:00:00',
            'up_at' => null,
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-12 22:00:00',
            'up_at' => '2026-04-12 23:00:00',
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => '2026-04-20 00:00:00',
            'up_at' => null,
        ]);

        $digest = app(WeeklyMonitoringDigestService::class)->buildForUser($user, Date::parse('2026-04-19'));

        $this->assertSame(3, $digest['overview']['incidents_count']);
        $this->assertSame(119, $digest['overview']['longest_downtime_minutes']);
        $this->assertSame(3, $digest['monitorings'][0]['incidents_count']);
        $this->assertSame(119, $digest['monitorings'][0]['longest_downtime_minutes']);
    }
}
