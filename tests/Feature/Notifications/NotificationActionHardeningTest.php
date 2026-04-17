<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class NotificationActionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        Package::factory()->create();
        $actingUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherMonitoring = Monitoring::factory()->for($otherUser)->create();
        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $otherMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL certificate expires soon.',
            'read' => false,
            'sent' => false,
        ]);

        $testResponse = $this->actingAs($actingUser)->post(route('notifications.markAsRead', $monitoringNotification->id));

        $testResponse->assertNotFound();
        $this->assertDatabaseHas('monitoring_notifications', [
            'id' => $monitoringNotification->id,
            'read' => false,
        ]);
    }

    public function test_load_more_rejects_unknown_notification_type(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => 'unexpected',
            'offset' => 0,
            'show_read' => false,
        ]);

        $testResponse->assertUnprocessable();
        $testResponse->assertJsonValidationErrors(['type']);
    }

    public function test_marking_a_status_change_as_read_marks_the_visible_status_thread_as_read(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);

        $latestNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        $testResponse = $this->actingAs($user)->post(route('notifications.markAsRead', $latestNotification->id));

        $testResponse->assertRedirect();
        $this->assertDatabaseHas('monitoring_notifications', [
            'id' => $monitoringNotification->id,
            'read' => true,
        ]);
        $this->assertDatabaseHas('monitoring_notifications', [
            'id' => $latestNotification->id,
            'read' => true,
        ]);
    }
}
