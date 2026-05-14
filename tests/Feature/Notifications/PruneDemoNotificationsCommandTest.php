<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PruneDemoNotificationsCommandTest extends TestCase
{
    public function test_prunes_old_demo_user_notifications_only(): void
    {
        Package::factory()->create();
        $demoUser = User::factory()->create(['role' => UserRole::DEMO]);
        $regularUser = User::factory()->create();
        $demoMonitoring = Monitoring::factory()->for($demoUser)->create();
        $regularMonitoring = Monitoring::factory()->for($regularUser)->create();

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $demoMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
        ]);
        $monitoringNotification->forceFill([
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ])->save();
        $recentDemoNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $demoMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
        ]);
        $recentDemoNotification->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();
        $oldRegularNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $regularMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
        ]);
        $oldRegularNotification->forceFill([
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ])->save();

        Artisan::call('notifications:prune-demo');

        $this->assertDatabaseMissing('monitoring_notifications', ['id' => $monitoringNotification->id]);
        $this->assertDatabaseHas('monitoring_notifications', ['id' => $recentDemoNotification->id]);
        $this->assertDatabaseHas('monitoring_notifications', ['id' => $oldRegularNotification->id]);
    }
}
