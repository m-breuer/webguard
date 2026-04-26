<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ServerInstance $serverInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $this->user = User::factory()->create([
            'package_id' => $package->id,
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.test/services/test',
                ],
                'webhook' => [
                    'enabled' => false,
                    'url' => 'https://example.test/webhook',
                ],
                'telegram' => [
                    'enabled' => true,
                    'bot_token' => '12345:ABCDEF',
                    'chat_id' => '-100123456',
                ],
            ],
        ]);

        $this->serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $this->serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }

    public function test_edit_page_shows_only_enabled_profile_channels_for_monitoring_notifications(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'preferred_location' => $this->serverInstance->code,
            'notification_channels' => ['slack'],
            'ssl_expiry_warning_days' => 14,
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.form.notification_channels'));
        $testResponse->assertSeeText(__('profile.notification_settings.channels.slack.title'));
        $testResponse->assertSeeText(__('profile.notification_settings.channels.telegram.title'));
        $testResponse->assertDontSeeText(__('profile.notification_settings.channels.webhook.title'));
        $testResponse->assertSeeHtml('value="14"');
    }

    public function test_update_persists_per_monitoring_channels_and_ssl_warning_window(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'preferred_location' => $this->serverInstance->code,
            'notification_channels' => ['slack'],
            'ssl_expiry_warning_days' => 7,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => '200-299',
        ]);

        $testResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), [
            'name' => $monitoring->name,
            'type' => $monitoring->type->value,
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => '200-299',
            'preferred_location' => $this->serverInstance->code,
            'notification_on_failure' => '1',
            'notification_channels' => ['telegram'],
            'ssl_expiry_warning_days' => 21,
        ]);

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();

        $this->assertSame(['telegram'], $monitoring->notification_channels);
        $this->assertSame(21, $monitoring->ssl_expiry_warning_days);
    }

    public function test_update_rejects_channels_that_are_not_enabled_in_profile(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'preferred_location' => $this->serverInstance->code,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => '200-299',
        ]);

        $testResponse = $this->from(route('monitorings.edit', $monitoring))
            ->actingAs($this->user)
            ->patch(route('monitorings.update', $monitoring), [
                'name' => $monitoring->name,
                'type' => $monitoring->type->value,
                'status' => MonitoringLifecycleStatus::ACTIVE->value,
                'timeout' => 5,
                'http_method' => 'get',
                'expected_http_statuses' => '200-299',
                'preferred_location' => $this->serverInstance->code,
                'notification_on_failure' => '1',
                'notification_channels' => ['webhook'],
                'ssl_expiry_warning_days' => 21,
            ]);

        $testResponse->assertRedirect(route('monitorings.edit', $monitoring));
        $testResponse->assertSessionHasErrors(['notification_channels.0']);
    }
}
