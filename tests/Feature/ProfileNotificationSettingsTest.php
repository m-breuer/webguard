<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_test_notification_to_each_saved_channel(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        Package::factory()->create();

        $channelConfigurations = [
            'slack' => [
                'notificationChannels' => [
                    'slack' => [
                        'enabled' => false,
                        'webhook_url' => 'https://hooks.slack.com/services/T000/B000/XXX',
                    ],
                ],
                'expectedUrl' => 'https://hooks.slack.com/services/T000/B000/XXX',
            ],
            'telegram' => [
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
                'notificationChannels' => [
                    'discord' => [
                        'enabled' => false,
                        'webhook_url' => 'https://discord.com/api/webhooks/123/token',
                    ],
                ],
                'expectedUrl' => 'https://discord.com/api/webhooks/123/token',
            ],
            'teams' => [
                'notificationChannels' => [
                    'teams' => [
                        'enabled' => false,
                        'webhook_url' => 'https://example.com/teams/webhook/123',
                    ],
                ],
                'expectedUrl' => 'https://example.com/teams/webhook/123',
            ],
            'webhook' => [
                'notificationChannels' => [
                    'webhook' => [
                        'enabled' => false,
                        'url' => 'https://example.com/webhooks/webguard',
                    ],
                ],
                'expectedUrl' => 'https://example.com/webhooks/webguard',
            ],
        ];

        foreach ($channelConfigurations as $channel => $configuration) {
            $user = User::factory()->create([
                'notification_channels' => $configuration['notificationChannels'],
            ]);

            $testResponse = $this->actingAs($user)
                ->post(route('profile.notification-channels.test', ['channel' => $channel]));

            $testResponse->assertRedirect();
            $testResponse->assertSessionHas('success', __('profile.notification_settings.test.messages.sent', [
                'channel' => __('profile.notification_settings.channels.' . $channel . '.title'),
            ]));
        }

        foreach ($channelConfigurations as $channelConfiguration) {
            Http::assertSent(fn ($request): bool => $request->url() === $channelConfiguration['expectedUrl']
                && str_contains(json_encode($request->data(), JSON_THROW_ON_ERROR), __('profile.notification_settings.test.payload.title')));
        }

        Http::assertSentCount(count($channelConfigurations));
    }

    public function test_profile_page_shows_notification_settings_and_one_time_hint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => null,
            'notification_channels_hint_seen_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->get(route('profile.edit', ['full' => 1]));
        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-profile-settings')
            ->assertSeeHtml('id="profile-information"')
            ->assertSeeHtml('id="profile-password"')
            ->assertSeeHtml('id="profile-api"')
            ->assertSeeHtml('id="profile-delete"');
        $testResponse->assertSeeText(__('profile.sections.account'));
        $testResponse->assertSeeText(__('profile.sections.preferences'));
        $testResponse->assertSeeText(__('profile.notification_settings.heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.channels_heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.digest.heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.unread_reminder.heading'));
        $testResponse->assertDontSeeText(__('profile.notification_settings.expiry_warning_days.heading'));
        $testResponse->assertSeeText(__('profile.notification_settings.hint_banner'));
        $testResponse->assertSeeHtml('data-confirm-message="' . __('api.configuration.messages.confirm_revoke_token') . '"');

        $secondResponse = $this->actingAs($user->fresh())->get(route('profile.edit', ['full' => 1]));
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
                    'webhook_url' => 'https://hooks.slack.com/services/T000/B000/XXX',
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
                'teams' => [
                    'enabled' => '1',
                    'webhook_url' => 'https://example.com/teams/webhook/123',
                ],
                'webhook' => [
                    'enabled' => '1',
                    'url' => 'https://example.com/webhooks/webguard',
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
        $this->assertSame('https://hooks.slack.com/services/T000/B000/XXX', data_get($user->notification_channels, 'slack.webhook_url'));
        $this->assertNull(data_get($user->notification_channels, 'slack.events'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'telegram.enabled'));
        $this->assertSame('12345:ABCDEF', data_get($user->notification_channels, 'telegram.bot_token'));
        $this->assertSame('-1001234567', data_get($user->notification_channels, 'telegram.chat_id'));
        $this->assertNull(data_get($user->notification_channels, 'telegram.events'));
        $this->assertFalse((bool) data_get($user->notification_channels, 'discord.enabled'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'teams.enabled'));
        $this->assertSame('https://example.com/teams/webhook/123', data_get($user->notification_channels, 'teams.webhook_url'));
        $this->assertTrue((bool) data_get($user->notification_channels, 'webhook.enabled'));
        $this->assertSame('https://example.com/webhooks/webguard', data_get($user->notification_channels, 'webhook.url'));
        $this->assertNull(data_get($user->notification_channels, 'webhook.events'));
    }

    public function test_profile_update_rejects_private_notification_webhook_urls(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'system',
        ]);

        foreach ([
            'slack' => ['notification_channels.slack.webhook_url', ['slack' => ['enabled' => '1', 'webhook_url' => 'http://127.0.0.1:8080/slack']]],
            'discord' => ['notification_channels.discord.webhook_url', ['discord' => ['enabled' => '1', 'webhook_url' => 'http://10.0.0.5/discord']]],
            'teams' => ['notification_channels.teams.webhook_url', ['teams' => ['enabled' => '1', 'webhook_url' => 'http://localhost/teams']]],
            'webhook' => ['notification_channels.webhook.url', ['webhook' => ['enabled' => '1', 'url' => 'http://[::1]/webhook']]],
        ] as [$field, $notificationChannels]) {
            $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'system',
                'notification_channels' => $notificationChannels,
            ]);

            $testResponse->assertSessionHasErrors([$field]);
        }
    }

    public function test_profile_update_allows_public_notification_webhook_urls(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'system',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'system',
            'notification_channels' => [
                'webhook' => [
                    'enabled' => '1',
                    'url' => 'https://example.com/webhooks/webguard',
                ],
            ],
        ]);

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHasNoErrors();
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

    public function test_profile_update_can_disable_notification_digest_and_unread_reminders(): void
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
            'monitoring_digest_frequency' => 'weekly',
            'unread_notifications_reminder_frequency' => 'daily',
        ]);

        $testResponse->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertFalse($user->monitoring_digest_enabled);
        $this->assertSame('weekly', $user->monitoring_digest_frequency);
        $this->assertFalse($user->unread_notifications_reminder_enabled);
        $this->assertSame('daily', $user->unread_notifications_reminder_frequency);
    }

    public function test_profile_update_defaults_blank_notification_frequencies(): void
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
            'monitoring_digest_frequency' => '',
            'unread_notifications_reminder_frequency' => '',
        ]);

        $testResponse->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertFalse($user->monitoring_digest_enabled);
        $this->assertSame('weekly', $user->monitoring_digest_frequency);
        $this->assertFalse($user->unread_notifications_reminder_enabled);
        $this->assertSame('daily', $user->unread_notifications_reminder_frequency);
    }

    public function test_profile_update_defaults_whitespace_notification_frequencies(): void
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
            'monitoring_digest_frequency' => '   ',
            'unread_notifications_reminder_frequency' => "\t",
        ]);

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->monitoring_digest_enabled);
        $this->assertSame('weekly', $user->monitoring_digest_frequency);
        $this->assertFalse($user->unread_notifications_reminder_enabled);
        $this->assertSame('daily', $user->unread_notifications_reminder_frequency);
    }

    public function test_profile_update_trims_notification_frequencies_before_validation(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'monitoring_digest_frequency' => 'monthly',
            'unread_notifications_reminder_frequency' => 'monthly',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'system',
            'monitoring_digest_enabled' => '1',
            'monitoring_digest_frequency' => ' weekly ',
            'unread_notifications_reminder_enabled' => '1',
            'unread_notifications_reminder_frequency' => "\tdaily\n",
        ]);

        $testResponse->assertRedirect(route('profile.edit'));
        $testResponse->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertTrue($user->monitoring_digest_enabled);
        $this->assertSame('weekly', $user->monitoring_digest_frequency);
        $this->assertTrue($user->unread_notifications_reminder_enabled);
        $this->assertSame('daily', $user->unread_notifications_reminder_frequency);
    }

    public function test_profile_update_defaults_missing_notification_frequencies(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'monitoring_digest_frequency' => 'monthly',
            'unread_notifications_reminder_frequency' => 'weekly',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'system',
            'monitoring_digest_enabled' => '1',
            'unread_notifications_reminder_enabled' => '1',
        ]);

        $testResponse->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertTrue($user->monitoring_digest_enabled);
        $this->assertSame('weekly', $user->monitoring_digest_frequency);
        $this->assertTrue($user->unread_notifications_reminder_enabled);
        $this->assertSame('daily', $user->unread_notifications_reminder_frequency);
    }

    public function test_profile_page_shows_notification_channel_test_buttons(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->get(route('profile.edit', ['modal' => 'profile-information']));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('aria-label="' . __('profile.notification_settings.test.action') . '"');
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'slack']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'telegram']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'discord']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'teams']));
        $testResponse->assertSeeHtml(route('profile.notification-channels.test', ['channel' => 'webhook']));
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

    public function test_profile_update_requires_teams_webhook_url_when_channel_is_enabled(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'system',
        ]);

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'system',
            'notification_channels' => [
                'teams' => [
                    'enabled' => '1',
                    'webhook_url' => '',
                ],
            ],
        ]);

        $testResponse->assertSessionHasErrors(['notification_channels.teams.webhook_url']);
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
                    'webhook_url' => 'https://hooks.slack.com/services/T000/B000/XXX',
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
