<x-container space="true">
    @php
        $notificationChannels = old('notification_channels', $user->notification_channels ?? []);
        $notificationChannelKeys = ['slack', 'telegram', 'discord', 'webhook'];
    @endphp

    <x-heading type="h2">{{ __('profile.information.heading') }}</x-heading>
    <x-paragraph>
        {{ __('profile.information.description') }}
    </x-paragraph>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <section class="space-y-4">
            <div>
                <x-heading type="h3">{{ __('profile.sections.account') }}</x-heading>
            </div>

            <div>
                <x-input-label for="name" :value="__('profile.fields.name')" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus
                    autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('profile.fields.email')" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required
                    autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                    <div>
                        <x-paragraph>
                            {{ __('profile.information.email_unverified') }}

                            <button form="send-verification"
                                class="focus:outline-hidden rounded-md text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                                {{ __('profile.information.send_verification_email') }}
                            </button>
                        </x-paragraph>
                    </div>
                @else
                    <div>
                        <x-paragraph class="text-green-600">
                            {{ __('profile.messages.email_verified') }}
                        </x-paragraph>
                    </div>
                @endif
            </div>
        </section>

        <section class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <x-heading type="h3">{{ __('profile.sections.preferences') }}</x-heading>
            </div>

            <div class="w-full md:w-1/2" x-data="{ theme: '{{ old('theme', $user->theme) }}' }">
                <x-input-label for="theme" :value="__('profile.fields.theme')" />
                <x-select-input id="theme" class="block w-full" name="theme"
                    @change="theme = $event.target.value">
                    <option value="light" :selected="theme === 'light'">{{ __('profile.fields.theme_light') }}
                    </option>
                    <option value="dark" :selected="theme === 'dark'">{{ __('profile.fields.theme_dark') }}
                    </option>
                    <option value="system" :selected="theme === 'system'">{{ __('profile.fields.theme_system') }}
                    </option>
                </x-select-input>
                <x-input-error :messages="$errors->get('theme')" />
            </div>
        </section>

        <section class="space-y-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <x-heading type="h2">{{ __('profile.notification_settings.heading') }}</x-heading>
            <x-paragraph>{{ __('profile.notification_settings.description') }}</x-paragraph>

            @if (!empty($showNotificationChannelsHint))
                <div class="rounded-xl border border-amber-300 bg-amber-50/80 p-4 dark:border-amber-700 dark:bg-amber-950/30">
                    <x-paragraph class="text-sm text-amber-900 dark:text-amber-200">
                        {{ __('profile.notification_settings.hint_banner') }}
                    </x-paragraph>
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <x-heading type="h3">{{ __('profile.notification_settings.channels_heading') }}</x-heading>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900/30">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.slack.title') }}</x-heading>
                        <x-text-checkbox id="notification_channels_slack_enabled" name="notification_channels[slack][enabled]"
                            :checked="(bool) data_get($notificationChannels, 'slack.enabled', false)"
                            :label="__('profile.notification_settings.enabled')" />
                        <x-secondary-button form="test-slack-notification-channel" class="text-xs">
                            {{ __('profile.notification_settings.test.action') }}
                        </x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('notification_channels.slack')" />

                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-300">{{ __('profile.notification_settings.channels.slack.help') }}</x-paragraph>

                    <div class="mt-4">
                        <x-input-label for="notification_channels_slack_webhook_url" :value="__('profile.notification_settings.fields.slack_webhook_url')" />
                        <x-text-input id="notification_channels_slack_webhook_url" name="notification_channels[slack][webhook_url]" type="url"
                            :value="data_get($notificationChannels, 'slack.webhook_url')" placeholder="https://hooks.slack.com/services/..." />
                        <x-input-error :messages="$errors->get('notification_channels.slack.webhook_url')" />
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900/30">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.telegram.title') }}</x-heading>
                        <x-text-checkbox id="notification_channels_telegram_enabled" name="notification_channels[telegram][enabled]"
                            :checked="(bool) data_get($notificationChannels, 'telegram.enabled', false)"
                            :label="__('profile.notification_settings.enabled')" />
                        <x-secondary-button form="test-telegram-notification-channel" class="text-xs">
                            {{ __('profile.notification_settings.test.action') }}
                        </x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('notification_channels.telegram')" />

                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-300">{{ __('profile.notification_settings.channels.telegram.help') }}</x-paragraph>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="notification_channels_telegram_bot_token" :value="__('profile.notification_settings.fields.telegram_bot_token')" />
                            <x-text-input id="notification_channels_telegram_bot_token" name="notification_channels[telegram][bot_token]" type="text"
                                :value="data_get($notificationChannels, 'telegram.bot_token')" />
                            <x-input-error :messages="$errors->get('notification_channels.telegram.bot_token')" />
                        </div>
                        <div>
                            <x-input-label for="notification_channels_telegram_chat_id" :value="__('profile.notification_settings.fields.telegram_chat_id')" />
                            <x-text-input id="notification_channels_telegram_chat_id" name="notification_channels[telegram][chat_id]" type="text"
                                :value="data_get($notificationChannels, 'telegram.chat_id')" />
                            <x-input-error :messages="$errors->get('notification_channels.telegram.chat_id')" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900/30">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.discord.title') }}</x-heading>
                        <x-text-checkbox id="notification_channels_discord_enabled" name="notification_channels[discord][enabled]"
                            :checked="(bool) data_get($notificationChannels, 'discord.enabled', false)"
                            :label="__('profile.notification_settings.enabled')" />
                        <x-secondary-button form="test-discord-notification-channel" class="text-xs">
                            {{ __('profile.notification_settings.test.action') }}
                        </x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('notification_channels.discord')" />

                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-300">{{ __('profile.notification_settings.channels.discord.help') }}</x-paragraph>

                    <div class="mt-4">
                        <x-input-label for="notification_channels_discord_webhook_url" :value="__('profile.notification_settings.fields.discord_webhook_url')" />
                        <x-text-input id="notification_channels_discord_webhook_url" name="notification_channels[discord][webhook_url]" type="url"
                            :value="data_get($notificationChannels, 'discord.webhook_url')" placeholder="https://discord.com/api/webhooks/..." />
                        <x-input-error :messages="$errors->get('notification_channels.discord.webhook_url')" />
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900/30">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.webhook.title') }}</x-heading>
                        <x-text-checkbox id="notification_channels_webhook_enabled" name="notification_channels[webhook][enabled]"
                            :checked="(bool) data_get($notificationChannels, 'webhook.enabled', false)"
                            :label="__('profile.notification_settings.enabled')" />
                        <x-secondary-button form="test-webhook-notification-channel" class="text-xs">
                            {{ __('profile.notification_settings.test.action') }}
                        </x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('notification_channels.webhook')" />

                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-300">{{ __('profile.notification_settings.channels.webhook.help') }}</x-paragraph>

                    <div class="mt-4">
                        <x-input-label for="notification_channels_webhook_url" :value="__('profile.notification_settings.fields.webhook_url')" />
                        <x-text-input id="notification_channels_webhook_url" name="notification_channels[webhook][url]" type="url"
                            :value="data_get($notificationChannels, 'webhook.url')" placeholder="https://example.com/webhook" />
                        <x-input-error :messages="$errors->get('notification_channels.webhook.url')" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900/30">
                <div class="mb-4">
                    <x-heading type="h3">{{ __('profile.notification_settings.digest.heading') }}</x-heading>
                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-300">{{ __('profile.notification_settings.digest.description') }}</x-paragraph>
                </div>

                <x-text-checkbox id="monitoring_digest_enabled" name="monitoring_digest_enabled"
                    :checked="(bool) old('monitoring_digest_enabled', $user->monitoring_digest_enabled)"
                    :label="__('profile.notification_settings.digest.enabled')" />
                <x-input-error :messages="$errors->get('monitoring_digest_enabled')" />

                <div class="mt-4 w-full md:w-1/2">
                    <x-input-label for="monitoring_digest_frequency" :value="__('profile.notification_settings.digest.frequency')" />
                    <x-select-input id="monitoring_digest_frequency" class="mt-1 block w-full" name="monitoring_digest_frequency">
                        @foreach (['daily', 'weekly', 'monthly'] as $frequency)
                            <option value="{{ $frequency }}" @selected(old('monitoring_digest_frequency', $user->monitoring_digest_frequency ?? 'weekly') === $frequency)>
                                {{ __('profile.notification_settings.digest.frequencies.' . $frequency) }}
                            </option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('monitoring_digest_frequency')" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900/30">
                <div class="mb-4">
                    <x-heading type="h3">{{ __('profile.notification_settings.unread_reminder.heading') }}</x-heading>
                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-300">{{ __('profile.notification_settings.unread_reminder.description') }}</x-paragraph>
                </div>

                <x-text-checkbox id="unread_notifications_reminder_enabled" name="unread_notifications_reminder_enabled"
                    :checked="(bool) old('unread_notifications_reminder_enabled', $user->unread_notifications_reminder_enabled ?? true)"
                    :label="__('profile.notification_settings.unread_reminder.enabled')" />
                <x-input-error :messages="$errors->get('unread_notifications_reminder_enabled')" />

                <div class="mt-4 w-full md:w-1/2">
                    <x-input-label for="unread_notifications_reminder_frequency" :value="__('profile.notification_settings.unread_reminder.frequency')" />
                    <x-select-input id="unread_notifications_reminder_frequency" class="mt-1 block w-full" name="unread_notifications_reminder_frequency">
                        @foreach (['daily', 'weekly', 'monthly'] as $frequency)
                            <option value="{{ $frequency }}" @selected(old('unread_notifications_reminder_frequency', $user->unread_notifications_reminder_frequency ?? 'daily') === $frequency)>
                                {{ __('profile.notification_settings.unread_reminder.frequencies.' . $frequency) }}
                            </option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('unread_notifications_reminder_frequency')" />
                </div>
            </div>
        </section>

        <x-primary-button>{{ __('button.update') }}</x-primary-button>
    </form>

    @foreach ($notificationChannelKeys as $notificationChannelKey)
        <form id="test-{{ $notificationChannelKey }}-notification-channel" method="POST"
            action="{{ route('profile.notification-channels.test', ['channel' => $notificationChannelKey]) }}">
            @csrf
        </form>
    @endforeach
</x-container>
