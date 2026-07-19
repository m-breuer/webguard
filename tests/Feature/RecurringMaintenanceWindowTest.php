<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaintenanceWindowRecurrence;
use App\Enums\TeamRole;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class RecurringMaintenanceWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_recurring_window_marks_monitoring_as_under_maintenance(): void
    {
        $user = $this->createUser();
        $monitoring = Monitoring::factory()->for($user)->create();
        $this->createWindow($monitoring, [
            'starts_at' => '2026-07-19 08:00:00',
            'duration_minutes' => 120,
            'timezone' => 'Europe/Berlin',
        ]);

        Date::setTestNow('2026-07-19 08:30:00');

        $this->assertTrue($monitoring->fresh()->isUnderMaintenance());
        $this->assertFalse($monitoring->fresh()->hasUpcomingMaintenance());
        $this->assertSame('2026-07-19 08:00', $monitoring->fresh()->currentOrUpcomingMaintenanceWindow()['starts_at']->format('Y-m-d H:i'));

        Date::setTestNow('2026-07-19 11:00:00');

        $this->assertFalse($monitoring->fresh()->isUnderMaintenance());
        $this->assertTrue($monitoring->fresh()->hasUpcomingMaintenance());

        Date::setTestNow();
    }

    public function test_recurring_window_can_target_a_group_and_respects_repeat_until(): void
    {
        $user = $this->createUser();
        $monitoring = Monitoring::factory()->for($user)->create();
        $group = MonitoringGroup::factory()->for($user)->create();
        $group->monitorings()->attach($monitoring);
        $window = MaintenanceWindow::query()->create([
            'monitoring_group_id' => $group->id,
            'starts_at' => '2026-07-01 08:00:00',
            'duration_minutes' => 60,
            'recurrence' => MaintenanceWindowRecurrence::MONTHLY,
            'repeat_until' => '2026-08-15 23:59:59',
            'timezone' => 'Europe/Berlin',
            'enabled' => true,
        ]);

        Date::setTestNow('2026-08-01 08:30:00');
        $this->assertTrue($monitoring->fresh()->isUnderMaintenance());

        Date::setTestNow('2026-09-01 08:30:00');
        $this->assertFalse($monitoring->fresh()->isUnderMaintenance());
        $this->assertNull($monitoring->fresh()->currentOrUpcomingMaintenanceWindow());
        $this->assertTrue($window->refresh()->enabled);

        Date::setTestNow();
    }

    public function test_user_can_create_and_disable_a_recurring_monitoring_window(): void
    {
        $user = $this->createUser();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);

        $this->actingAs($user)->post(route('maintenance.store'), [
            'mode' => 'recurring',
            'scope' => 'monitoring',
            'monitoring_id' => $monitoring->id,
            'recurring_starts_at' => '2026-07-20T09:00',
            'recurring_duration_minutes' => 90,
            'recurrence' => 'weekly',
            'recurring_repeat_until' => '2026-12-31',
            'recurring_timezone' => 'Europe/Berlin',
        ])->assertRedirect(route('maintenance.index'));

        $window = MaintenanceWindow::query()->firstOrFail();
        $this->assertSame($monitoring->id, $window->monitoring_id);
        $this->assertSame(MaintenanceWindowRecurrence::WEEKLY, $window->recurrence);

        $this->actingAs($user)->delete(route('maintenance.destroy'), [
            'maintenance_window_id' => $window->id,
        ])->assertRedirect(route('maintenance.index'));

        $this->assertFalse($window->refresh()->enabled);
    }

    public function test_public_label_shows_the_next_recurring_window(): void
    {
        $user = $this->createUser();
        $monitoring = Monitoring::factory()->for($user)->create(['public_label_enabled' => true]);
        $this->createWindow($monitoring, ['starts_at' => '2026-07-20 08:00:00']);
        Date::setTestNow('2026-07-19 10:00:00');

        $this->get(route('public-label', $monitoring))
            ->assertOk()
            ->assertSeeText(__('monitoring.public_label.maintenance.upcoming'));

        Date::setTestNow();
    }

    public function test_team_member_can_see_but_cannot_disable_a_team_recurring_window(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Operations']);
        $team->memberships()->create(['user_id' => $owner->id, 'role' => TeamRole::ADMIN]);
        $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        $monitoring = Monitoring::factory()->create(['team_id' => $team->id, 'created_by_user_id' => $owner->id]);
        $window = $this->createWindow($monitoring);

        $this->actingAs($member)->get(route('maintenance.index'))
            ->assertOk()
            ->assertSeeText(__('maintenance.recurring.heading'));

        $this->actingAs($member)->delete(route('maintenance.destroy'), [
            'maintenance_window_id' => $window->id,
        ])->assertSessionHasErrors('maintenance_window_id');

        $this->assertTrue($window->refresh()->enabled);
    }

    private function createUser(): User
    {
        $package = Package::factory()->create(['monitoring_limit' => 20]);

        return User::factory()->create(['package_id' => $package->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWindow(Monitoring $monitoring, array $overrides = []): MaintenanceWindow
    {
        return MaintenanceWindow::query()->create(array_merge([
            'monitoring_id' => $monitoring->id,
            'starts_at' => '2026-07-19 08:00:00',
            'duration_minutes' => 60,
            'recurrence' => MaintenanceWindowRecurrence::WEEKLY,
            'repeat_until' => null,
            'timezone' => 'Europe/Berlin',
            'enabled' => true,
        ], $overrides));
    }
}
