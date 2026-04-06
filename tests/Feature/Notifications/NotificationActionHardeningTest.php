<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
