<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Kernel;
use App\Enums\MonitoringStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationState;
use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use App\Models\MonitoringSslResult;
use App\Models\Package;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use ReflectionMethod;
use Tests\TestCase;

class UnderCoveredCommandBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_purge_soft_deleted_monitorings_removes_related_rows(): void
    {
        $monitoring = Monitoring::factory()->for(User::factory())->create();
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 123.45,
        ]);
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => now()->toDateString(),
            'uptime_total' => 60,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 100,
            'downtime_percentage' => 0,
            'unknown_percentage' => 0,
            'uptime_minutes' => 60,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => 123.45,
            'incidents_count' => 0,
        ]);
        MonitoringResponseArchived::query()->create([
            'id' => 'archived-response-1',
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'response_time' => 456.78,
            'created_at' => now()->subMonths(2),
        ]);
        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'valid' => true,
            'issuer' => 'Example CA',
            'expires_at' => now()->addMonth(),
            'checked_at' => now(),
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => now()->subHour(),
            'up_at' => now(),
        ]);
        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => true,
            'sent' => true,
        ]);
        $monitoring->delete();

        $this->artisan('monitoring:purge-soft-deleted')
            ->expectsOutput('Deleting soft-deleted monitorings and their related data...')
            ->assertSuccessful();

        $this->assertDatabaseMissing('monitorings', ['id' => $monitoring->id]);
        $this->assertSame(0, MonitoringResponse::query()->where('monitoring_id', $monitoring->id)->count());
        $this->assertSame(0, MonitoringNotification::query()->withoutGlobalScopes()->where('monitoring_id', $monitoring->id)->count());
    }

    public function test_purge_soft_deleted_monitorings_reports_empty_work(): void
    {
        $this->artisan('monitoring:purge-soft-deleted')
            ->expectsOutput('No soft-deleted monitorings found.')
            ->assertSuccessful();
    }

    public function test_prune_read_notifications_deletes_only_old_fully_read_notifications(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $monitoringNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is up',
            'read' => false,
            'sent' => true,
        ]);
        $monitoringNotification->forceFill(['created_at' => now()->subMonths(2)])->saveQuietly();
        MonitoringNotificationState::query()->where('monitoring_notification_id', $monitoringNotification->id)->update([
            'read_at' => now()->subMonth(),
        ]);
        $oldUnreadNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => false,
            'sent' => true,
        ]);
        $oldUnreadNotification->forceFill(['created_at' => now()->subMonths(2)])->saveQuietly();
        $recentReadNotification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is up',
            'read' => true,
            'sent' => true,
        ]);

        $this->artisan('notifications:prune-read')
            ->expectsOutput('Deleted 1 old read notifications.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('monitoring_notifications', ['id' => $monitoringNotification->id]);
        $this->assertDatabaseHas('monitoring_notifications', ['id' => $oldUnreadNotification->id]);
        $this->assertDatabaseHas('monitoring_notifications', ['id' => $recentReadNotification->id]);
    }

    public function test_create_admin_user_creates_admin_and_rejects_duplicates(): void
    {
        $this->artisan('user:create-admin admin@example.com')
            ->expectsOutput("Admin user 'admin@example.com' created successfully with password 'password'.")
            ->assertSuccessful();

        $model = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame(UserRole::ADMIN, $model->role);

        $this->artisan('user:create-admin admin@example.com')
            ->expectsOutput('A user with this email already exists.')
            ->assertFailed();
    }

    public function test_console_kernel_registers_commands_and_accepts_empty_schedule(): void
    {
        $kernel = resolve(Kernel::class);
        $schedule = resolve(Schedule::class);

        $scheduleMethod = new ReflectionMethod($kernel, 'schedule');
        $scheduleMethod->invoke($kernel, $schedule);

        $commandsMethod = new ReflectionMethod($kernel, 'commands');
        $commandsMethod->invoke($kernel);

        $this->assertArrayHasKey('monitoring:purge-soft-deleted', Artisan::all());
    }
}
