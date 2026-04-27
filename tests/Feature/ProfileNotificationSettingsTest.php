<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{channel: string, notificationChannels: array<string, mixed>, expectedUrl: string}>
     */
    public static function notificationChannelConfigurations(): array
    {
        return [
            'slack' => [
                'channel' => 'slack',
                'notificationChannels' => [
                    'slack' => [
                        'enabled' => false,
                        'webhook_url' => 'https://hooks.slack.test/services/T000/B000/XXX',
                    ],
                ],
                'expectedUrl' => 'https://hooks.slack.test/services/T000/B000/XXX',
            ],
            'telegram' => [
                'channel' => 'telegram',
                'notificationChannels' => [
                    'telegram' => [
                        'enabled' => false,
                        'bot_token' => '12345:ABCDEF',
                        'chat_id' => '-1001234567',
                    ],
                ],
                'expectedUrl' => 'https://api.telegram.org/bot12345:ABCDEF/sendMessage',
            ],
            'discord' => [
                'channel' => 'discord',
                'notificationChannels' => [
                    'discord' => [
                        'enabled' => false,
                        'webhook_url' => 'https://discord.test/api/webhooks/123/token',
                    ],
                ],
                'expectedUrl' => 'https://discord.test/api/webhooks/123/token',
            ],
            'webhook' => [
                'channel' => 'webhook',
                'notificationChannels' => [
                    'webhook' => [
                        'enabled' => false,
                        'url' => 'https://example.test/webhooks/webguard',
                    ],
                ],
                'expectedUrl' => 'https://example.test/webhooks/webguard',
            ],
        ];
    }

    public function test_profile_page_shows_notification_settings_and_one_time_hint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => null,
            'notification_channels_hint_seen_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->get(route('profile.edit'));
        $testResponse->assertOk();
        $testResponse->assertSeeText(__('profile.sections.account'));
        $testResponse->assertSeeText(__('profile.sections.preferences'));
        $testResponse->assertSeeText(__('profile.notification_settings.heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.channels_heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.digest.heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.unread_reminder.heading'));
        $testResponse->assertDontSeeText(__('profile.notification_settings.expiry_warning_days.heading'));
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
            'monitoring_digest_enabled' => '1',
            'monitoring_digest_frequency' => 'monthly',
            'unread_notifications_reminder_enabled' => '1',
            'unread_notifications_reminder_frequency' => 'monthly',
            'notification_channels' => [
                'slack' => [
                    'enabled' => '1',
                    'webhook_url' => 'https://hooks.slack.test/services/T000/B000/XXX',
                ],
                'telegram' => [
                    'enabled' => '1',
                    'bot_token' => '12345:ABCDEF',
                    'chat_id' => '-1001234567',
                    'events' => [
                        'incident' => '1',
                        'ssl_expiring' => '1',
                        'domain_expiring' => '1',
                    ],
                ],
                'discord' => [
                    'enabled' => '0',
                    'webhook_url' => '',
                ],
                'webhook' => [
                    'enabled' => '1',
                    'url' => 'https://example.test/webhooks/webguard',
                ],
            ],
        ]);

        $testResponse->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('dark', $user->theme);
        $this->assertIsArray($user->notification_channels);
        $this->assertTrue($user->monitoring_digest_enabled);
        $this->assertSame('monthly', $user->monitoring_digest_frequency);
        $this->assertTrue($user->unread_notifications_reminder_enabled);
        $this->assertSame('monthly', $user->unread_notifications_reminder_frequency);
        $this->assertTrue((bool) data_get($user->notification_channels, 'slack.enabled'));
        $this->assertSame('https://hooks.slack.test/services/T000/B000/XXX', data_get($user->notification_channels, 'slack.webhook_url'));
        $this->assertNull(data_get($user->notification_channels, 'slack.events'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'telegram.enabled'));
        $this->assertSame('12345:ABCDEF', data_get($user->notification_channels, 'telegram.bot_token'));
        $this->assertSame('-1001234567', data_get($user->notification_channels, 'telegram.chat_id'));
        $this->assertNull(data_get($user->notification_channels, 'telegram.events'));
        $this->assertFalse((bool) data_get($user->notification_channels, 'discord.enabled'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'webhook.enabled'));
        $this->assertSame('https://example.test/webhooks/webguard', data_get($user->notification_channels, 'webhook.url'));
        $this->assertNull(data_get($user->notification_channels, 'webhook.events'));
    }

    public function test_profile_update_defaults_optional_notification_settings_when_omitted(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'monitoring_digest_enabled' => true,
            'monitoring_digest_frequency' => 'monthly',
            'unread_notifications_reminder_enabled' => true,
            'unread_notifications_reminder_frequency' => 'weekly',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'system',
        ]);

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->monitoring_digest_enabled);
        $this->assertSame('weekly', $user->monitoring_digest_frequency);
        $this->assertFalse($user->unread_notifications_reminder_enabled);
        $this->assertSame('daily', $user->unread_notifications_reminder_frequency);
    }

    public function test_profile_page_shows_notification_channel_test_buttons(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->get(route('profile.edit'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('profile.notification_settings.test.action'));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'slack']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'telegram']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'discord']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'webhook']));
    }

    /**
     * @param  array<string, mixed>  $notificationChannels
     */
    #[DataProvider('notificationChannelConfigurations')]
    public function test_user_can_send_test_notification_to_saved_channel(
        string $channel,
        array $notificationChannels,
        string $expectedUrl
    ): void {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => $notificationChannels,
        ]);

        $testResponse = $this->actingAs($user)->post(route('profile.notification-channels.test', ['channel' => $channel]));

        $testResponse->assertRedirect();
        $testResponse->assertSessionHas('success', __('profile.notification_settings.test.messages.sent', [
            'channel' => __('profile.notification_settings.channels.' . $channel . '.title'),
        ]));

        Http::assertSent(fn ($request): bool => $request->url() === $expectedUrl
            && str_contains(json_encode($request->data(), JSON_THROW_ON_ERROR), __('profile.notification_settings.test.payload.title')));
    }

    public function test_channel_test_requires_saved_channel_configuration(): void
    {
        Http::fake();

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [],
        ]);

        $testResponse = $this->actingAs($user)->from(route('profile.edit'))
            ->post(route('profile.notification-channels.test', ['channel' => 'slack']));

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHasErrors(['notification_channels.slack']);
        Http::assertNothingSent();
    }

    public function test_channel_test_reports_delivery_failure(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => false,
                    'webhook_url' => 'https://hooks.slack.test/services/T000/B000/XXX',
                ],
            ],
        ]);

        $testResponse = $this->actingAs($user)->from(route('profile.edit'))
            ->post(route('profile.notification-channels.test', ['channel' => 'slack']));

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHasErrors(['notification_channels.slack']);
        Http::assertSentCount(1);
    }
}
