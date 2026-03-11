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

class NotificationRenderingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_renders_without_lazy_loading_for_status_change_messages(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();
        $testResponse->assertSee($monitoring->name);
    }
}
