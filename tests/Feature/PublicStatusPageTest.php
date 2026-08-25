<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Mail\StatusPageSubscriptionConfirmationMail;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\StatusPageSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Js;
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
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.heading'));
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.active'));
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.starts_at'));
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.ends_at'));
        $testResponse->assertSeeText('Last 7 days');
        $testResponse->assertSeeText('Last 30 days');
        $testResponse->assertSeeText('Last 90 days');
        $testResponse->assertSeeText('100.00%');
        $testResponse->assertSeeText(__('monitoring.detail.incidents.heading'));
        $testResponse->assertSeeText(__('monitoring.public_label.resolved'));
        $testResponse->assertSeeText(__('monitoring.public_label.subscribe.heading'));
    }

    public function test_public_status_page_loads_uptime_calendar_without_authentication(): void
    {
        Date::setTestNow('2026-05-03 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Public Calendar API',
            'public_label_enabled' => true,
            'created_at' => Date::parse('2026-04-01 00:00:00'),
        ]);

        $this->createDailyResult($monitoring, '2026-04-10');

        $this->get(route('public-label', $monitoring))
            ->assertOk()->assertSeeHtml('uptimeCalendar')->assertSeeHtml(Js::from(route('public.monitorings.uptime-calendar', $monitoring))->toHtml());

        $testResponse = $this->getJson(route('public.monitorings.uptime-calendar', $monitoring) . '?' . http_build_query([
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ]));

        $testResponse->assertOk()
            ->assertJsonCount(30, '2026-04.days')
            ->assertJsonPath('2026-04.days.9.uptime_percentage', 100)
            ->assertJsonPath('2026-04.monthly_average_uptime', 100);
    }

    public function test_public_status_page_calendar_endpoint_validates_date_range_without_authentication(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => true,
        ]);

        $testResponse = $this->getJson(route('public.monitorings.uptime-calendar', $monitoring));

        $testResponse->assertUnprocessable();
        $testResponse->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_public_status_page_accepts_email_subscriptions_and_sends_confirmation(): void
    {
        Mail::fake();

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'public_label_enabled' => true,
        ]);

        $testResponse = $this->post(route('public-status-pages.subscribers.store', $monitoring), [
            'email' => 'Customer@Example.com',
        ]);

        $testResponse->assertRedirect(route('public-status-pages.show', [
            'statusPage' => $monitoring,
            'subscription' => 'confirmation-sent',
        ]));

        $statusPageSubscriber = StatusPageSubscriber::query()->firstOrFail();
        $this->assertSame($monitoring->id, $statusPageSubscriber->monitoring_id);
        $this->assertSame('customer@example.com', $statusPageSubscriber->email);
        $this->assertNull($statusPageSubscriber->verified_at);
        $this->assertNotNull($statusPageSubscriber->confirmation_token_hash);

        Mail::assertSent(StatusPageSubscriptionConfirmationMail::class, function (StatusPageSubscriptionConfirmationMail $statusPageSubscriptionConfirmationMail): bool {
            return $statusPageSubscriptionConfirmationMail->hasTo('customer@example.com') && filled($statusPageSubscriptionConfirmationMail->token);
        });
    }

    public function test_public_status_page_confirms_email_subscription(): void
    {
        Mail::fake();

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => true,
        ]);

        $this->post(route('public-label.subscribers.store', $monitoring), [
            'email' => 'customer@example.com',
        ]);

        $confirmationToken = null;
        Mail::assertSent(StatusPageSubscriptionConfirmationMail::class, function (StatusPageSubscriptionConfirmationMail $statusPageSubscriptionConfirmationMail) use (&$confirmationToken): bool {
            $confirmationToken = $statusPageSubscriptionConfirmationMail->token;

            return true;
        });

        $testResponse = $this->get(route('public-label.subscribers.confirm', [
            'monitoring' => $monitoring,
            'token' => $confirmationToken,
        ]));

        $testResponse->assertRedirect(route('public-label', [
            'monitoring' => $monitoring,
            'subscription' => 'confirmed',
        ]));
        $this->assertTrue(StatusPageSubscriber::query()->firstOrFail()->isVerified());
        $this->assertNull(StatusPageSubscriber::query()->firstOrFail()->confirmation_token_hash);
    }

    public function test_public_status_page_does_not_reset_verified_subscription(): void
    {
        Mail::fake();

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => true,
        ]);
        $statusPageSubscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'customer@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => Date::now()->subDay(),
        ]);

        $this->post(route('public-label.subscribers.store', $monitoring), [
            'email' => 'customer@example.com',
        ])->assertRedirect(route('public-label', [
            'monitoring' => $monitoring,
            'subscription' => 'confirmation-sent',
        ]));

        $statusPageSubscriber->refresh();
        $this->assertTrue($statusPageSubscriber->isVerified());
        $this->assertSame('unsubscribe-token', $statusPageSubscriber->unsubscribe_token);
        Mail::assertNothingSent();
    }

    public function test_public_status_page_unsubscribes_email_subscriber(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => true,
        ]);
        $statusPageSubscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'customer@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => Date::now(),
        ]);

        $unsubscribeResponse = $this->get(route('public-label.subscribers.unsubscribe', [
            'monitoring' => $monitoring,
            'token' => $statusPageSubscriber->unsubscribe_token,
        ]));

        $unsubscribeResponse->assertOk();
        $unsubscribeResponse->assertSeeHtml('x-data="confirmDialog()"');
        $unsubscribeResponse->assertSeeHtml('data-confirm-message="' . __('monitoring.public_label.subscribe.unsubscribe_confirmation') . '"');

        $testResponse = $this->delete(route('public-label.subscribers.destroy', [
            'monitoring' => $monitoring,
            'token' => $statusPageSubscriber->unsubscribe_token,
        ]), [
            'email' => $statusPageSubscriber->email,
        ]);

        $testResponse->assertRedirect(route('public-label', [
            'monitoring' => $monitoring,
            'subscription' => 'unsubscribed',
        ]));
        $this->assertDatabaseMissing('status_page_subscribers', [
            'id' => $statusPageSubscriber->id,
        ]);
    }

    public function test_public_status_page_unsubscribe_email_match_is_case_insensitive(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => true,
        ]);
        $statusPageSubscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'customer@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => Date::now(),
        ]);

        $testResponse = $this->delete(route('public-label.subscribers.destroy', [
            'monitoring' => $monitoring,
            'token' => $statusPageSubscriber->unsubscribe_token,
        ]), [
            'email' => 'Customer@Example.com',
        ]);

        $testResponse->assertRedirect(route('public-label', [
            'monitoring' => $monitoring,
            'subscription' => 'unsubscribed',
        ]));
        $this->assertDatabaseMissing('status_page_subscribers', [
            'id' => $statusPageSubscriber->id,
        ]);
    }

    public function test_status_page_unsubscribe_link_works_after_public_page_is_disabled(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);
        $statusPageSubscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'customer@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => Date::now(),
        ]);

        $this->get(route('public-label.subscribers.unsubscribe', [
            'monitoring' => $monitoring,
            'token' => $statusPageSubscriber->unsubscribe_token,
        ]))->assertOk();

        $testResponse = $this->delete(route('public-label.subscribers.destroy', [
            'monitoring' => $monitoring,
            'token' => $statusPageSubscriber->unsubscribe_token,
        ]), [
            'email' => $statusPageSubscriber->email,
        ]);

        $testResponse->assertRedirect('/');
        $this->assertDatabaseMissing('status_page_subscribers', [
            'id' => $statusPageSubscriber->id,
        ]);
    }

    public function test_public_status_page_shows_upcoming_maintenance_window(): void
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
            'maintenance_from' => Date::now()->addDay(),
            'maintenance_until' => Date::now()->addDay()->addHours(2),
        ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.heading'));
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.upcoming'));
        $testResponse->assertSeeText(Date::now()->addDay()->locale(app()->getLocale())->isoFormat('L LT'));
        $testResponse->assertSeeText(Date::now()->addDay()->addHours(2)->locale(app()->getLocale())->isoFormat('L LT'));
        $testResponse->assertDontSeeText(__('monitoring.public_label.maintenance.active'));
    }

    public function test_public_status_page_hides_expired_maintenance_window(): void
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
            'maintenance_from' => Date::now()->subDays(2),
            'maintenance_until' => Date::now()->subDay(),
        ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertDontSeeText(__('monitoring.public_label.maintenance.heading'));
        $testResponse->assertDontSeeText(__('monitoring.public_label.maintenance.active'));
        $testResponse->assertDontSeeText(__('monitoring.public_label.maintenance.upcoming'));
    }

    public function test_public_status_page_shows_open_ended_maintenance_window(): void
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
            'maintenance_until' => null,
        ]);

        $testResponse = $this->get(route('public-label', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.heading'));
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.active'));
        $testResponse->assertSeeText(__('monitoring.public_label.maintenance.open_ended'));
        $testResponse->assertSeeText(Date::now()->subHour()->locale(app()->getLocale())->isoFormat('L LT'));
        $testResponse->assertDontSeeText(__('monitoring.public_label.maintenance.upcoming'));
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
