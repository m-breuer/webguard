<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PublicStatusPageMaintenanceScheduledMail;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\StatusPageMaintenanceDelivery;
use App\Models\StatusPageSubscription;
use App\Models\User;
use App\Services\PlannedMaintenanceNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlannedMaintenanceSubscriberNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_one_off_maintenance_notifies_verified_subscribers_of_the_affected_public_status_page(): void
    {
        Mail::fake();
        $user = $this->user();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $statusPage = $this->statusPage($user, $monitoring);
        $verifiedSubscription = $this->subscription($statusPage, 'verified@example.com', true);
        $this->subscription($statusPage, 'pending@example.com', false);
        $outsideMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Internal Worker']);
        $outsideStatusPage = $this->statusPage($user, $outsideMonitoring, 'Internal Status');
        $this->subscription($outsideStatusPage, 'outside@example.com', true);

        $this->actingAs($user)->post(route('maintenance.store'), [
            'scope' => 'monitoring',
            'monitoring_id' => $monitoring->id,
            'maintenance_from' => '2026-08-20T10:00:00+00:00',
            'maintenance_until' => '2026-08-20T11:00:00+00:00',
        ])->assertRedirect(route('maintenance.index'));

        Mail::assertSent(PublicStatusPageMaintenanceScheduledMail::class, function (PublicStatusPageMaintenanceScheduledMail $mail) use ($verifiedSubscription, $monitoring): bool {
            return $mail->hasTo('verified@example.com')
                && $mail->subscription->is($verifiedSubscription)
                && $mail->monitorings->contains($monitoring)
                && ! $mail->recurring;
        });
        Mail::assertNotSent(PublicStatusPageMaintenanceScheduledMail::class, fn (PublicStatusPageMaintenanceScheduledMail $mail): bool => $mail->hasTo('pending@example.com'));
        Mail::assertNotSent(PublicStatusPageMaintenanceScheduledMail::class, fn (PublicStatusPageMaintenanceScheduledMail $mail): bool => $mail->hasTo('outside@example.com'));
        $this->assertDatabaseHas('status_page_maintenance_deliveries', [
            'status_page_subscription_id' => $verifiedSubscription->id,
        ]);
    }

    public function test_recurring_group_maintenance_notifies_subscribers_once_with_all_affected_services(): void
    {
        Mail::fake();
        Date::setTestNow('2026-08-16 09:00:00 UTC');
        $this->beforeApplicationDestroyed(fn (): mixed => Date::setTestNow());

        $user = $this->user();
        $group = MonitoringGroup::factory()->for($user)->create(['name' => 'Checkout']);
        $firstMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $secondMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout Worker']);
        $group->monitorings()->attach([$firstMonitoring->id, $secondMonitoring->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPage->components()->create([
            'name' => 'Checkout',
            'position' => 0,
            'monitoring_group_id' => $group->id,
        ]);
        $subscription = $this->subscription($statusPage, 'subscriber@example.com', true);

        $this->actingAs($user)->post(route('maintenance.store'), [
            'mode' => 'recurring',
            'scope' => 'group',
            'monitoring_group_id' => $group->id,
            'recurring_starts_at' => '2026-08-20T10:00:00',
            'recurring_duration_minutes' => 90,
            'recurrence' => 'weekly',
            'recurring_timezone' => 'Europe/Berlin',
        ])->assertRedirect(route('maintenance.index'));

        Mail::assertSent(PublicStatusPageMaintenanceScheduledMail::class, function (PublicStatusPageMaintenanceScheduledMail $mail) use ($subscription, $firstMonitoring, $secondMonitoring): bool {
            return $mail->hasTo('subscriber@example.com')
                && $mail->subscription->is($subscription)
                && $mail->monitorings->contains($firstMonitoring)
                && $mail->monitorings->contains($secondMonitoring)
                && $mail->recurring;
        });
    }

    public function test_a_maintenance_revision_is_not_sent_twice_to_the_same_subscriber(): void
    {
        Mail::fake();
        $user = $this->user();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $statusPage = $this->statusPage($user, $monitoring);
        $subscription = $this->subscription($statusPage, 'subscriber@example.com', true);
        $service = app(PlannedMaintenanceNotificationService::class);
        $startsAt = Date::parse('2026-08-20 10:00:00 UTC');
        $endsAt = Date::parse('2026-08-20 11:00:00 UTC');

        $service->notifyForOneOff(collect([$monitoring]), $startsAt, $endsAt);
        $service->notifyForOneOff(collect([$monitoring]), $startsAt, $endsAt);

        Mail::assertSent(PublicStatusPageMaintenanceScheduledMail::class, 1);
        $this->assertSame(1, StatusPageMaintenanceDelivery::query()
            ->where('status_page_subscription_id', $subscription->id)
            ->count());
    }

    public function test_a_material_maintenance_change_creates_a_new_subscriber_notice(): void
    {
        Mail::fake();
        $user = $this->user();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $statusPage = $this->statusPage($user, $monitoring);
        $subscription = $this->subscription($statusPage, 'subscriber@example.com', true);
        $service = app(PlannedMaintenanceNotificationService::class);
        $startsAt = Date::parse('2026-08-20 10:00:00 UTC');

        $service->notifyForOneOff(collect([$monitoring]), $startsAt, Date::parse('2026-08-20 11:00:00 UTC'));
        $service->notifyForOneOff(collect([$monitoring]), $startsAt, Date::parse('2026-08-20 12:00:00 UTC'));

        Mail::assertSent(PublicStatusPageMaintenanceScheduledMail::class, 2);
        $this->assertSame(2, StatusPageMaintenanceDelivery::query()
            ->where('status_page_subscription_id', $subscription->id)
            ->count());
    }

    private function user(): User
    {
        return User::factory()->create(['package_id' => Package::factory()->create()->id]);
    }

    private function statusPage(User $user, Monitoring $monitoring, string $name = 'Acme Status'): StatusPage
    {
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => (string) str($name)->slug(),
            'is_public' => true,
        ]);
        $component = $statusPage->components()->create(['name' => $name, 'position' => 0]);
        $component->monitorings()->attach($monitoring->id, ['position' => 0]);

        return $statusPage;
    }

    private function subscription(StatusPage $statusPage, string $email, bool $verified): StatusPageSubscription
    {
        return StatusPageSubscription::query()->create([
            'status_page_id' => $statusPage->id,
            'email' => $email,
            'confirmation_token_hash' => $verified ? null : StatusPageSubscription::hashToken('pending-token'),
            'unsubscribe_token' => str($email)->before('@')->append('-token'),
            'verified_at' => $verified ? Date::now() : null,
        ]);
    }
}
