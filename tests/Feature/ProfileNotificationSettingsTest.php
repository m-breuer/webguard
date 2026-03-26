<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_notification_settings_and_one_time_hint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => null,
            'notification_channels_hint_seen_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->get(route('profile.edit'));
        $testResponse->assertOk();
        $testResponse->assertSeeText(__('profile.notification_settings.heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.email_removed_notice'));
        $testResponse->assertSeeText(__('profile.notification_settings.hint_banner'));

        $secondResponse = $this->actingAs($user->fresh())->get(route('profile.edit'));
        $secondResponse->assertOk();
        $secondResponse->assertDontSeeText(__('profile.notification_settings.hint_banner'));
    }

    public function test_profile_update_persists_notification_channel_settings(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'system',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'dark',
            'notification_channels' => [
                'slack' => [
                    'enabled' => '1',
                    'webhook_url' => 'https://hooks.slack.test/services/T000/B000/XXX',
                    'events' => [
                        'incident' => '1',
                        'recovery' => '1',
                    ],
                ],
                'telegram' => [
                    'enabled' => '1',
                    'bot_token' => '12345:ABCDEF',
                    'chat_id' => '-1001234567',
                    'events' => [
                        'incident' => '1',
                        'ssl_expiring' => '1',
                    ],
                ],
                'discord' => [
                    'enabled' => '0',
                    'webhook_url' => '',
                    'events' => [],
                ],
                'webhook' => [
                    'enabled' => '1',
                    'url' => 'https://example.test/webhooks/webguard',
                    'events' => [
                        'ssl_expired' => '1',
                    ],
                ],
            ],
        ]);

        $testResponse->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('dark', $user->theme);
        $this->assertIsArray($user->notification_channels);
        $this->assertTrue((bool) data_get($user->notification_channels, 'slack.enabled'));
        $this->assertSame('https://hooks.slack.test/services/T000/B000/XXX', data_get($user->notification_channels, 'slack.webhook_url'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'slack.events.incident'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'slack.events.recovery'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'telegram.enabled'));
        $this->assertSame('12345:ABCDEF', data_get($user->notification_channels, 'telegram.bot_token'));
        $this->assertSame('-1001234567', data_get($user->notification_channels, 'telegram.chat_id'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'telegram.events.incident'));
        $this->assertFalse((bool) data_get($user->notification_channels, 'telegram.events.recovery'));
        $this->assertFalse((bool) data_get($user->notification_channels, 'discord.enabled'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'webhook.enabled'));
        $this->assertSame('https://example.test/webhooks/webguard', data_get($user->notification_channels, 'webhook.url'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'webhook.events.ssl_expired'));
    }
}
