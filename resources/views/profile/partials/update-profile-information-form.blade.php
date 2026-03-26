<x-container space="true">
    @php
        $notificationChannels = old('notification_channels', $user->notification_channels ?? []);
        $eventTypes = ['incident', 'recovery', 'ssl_expiring', 'ssl_expired'];
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

        <div class="space-y-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <x-heading type="h2">{{ __('profile.notification_settings.heading') }}</x-heading>
            <x-paragraph>{{ __('profile.notification_settings.description') }}</x-paragraph>

            @if (!empty($showNotificationChannelsHint))
                <div class="rounded-md border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-600 dark:bg-amber-950 dark:text-amber-200">
                    {{ __('profile.notification_settings.hint_banner') }}
                </div>
            @endif

            <div class="rounded-md border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
                {{ __('profile.notification_settings.email_removed_notice') }}
            </div>

            <div class="space-y-6">
                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.slack.title') }}</x-heading>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="notification_channels[slack][enabled]" value="1"
                                @checked(data_get($notificationChannels, 'slack.enabled', false))>
                            <span>{{ __('profile.notification_settings.enabled') }}</span>
                        </label>
                    </div>

                    <x-paragraph>{{ __('profile.notification_settings.channels.slack.help') }}</x-paragraph>
                    <x-text-input id="notification_channels_slack_webhook_url" name="notification_channels[slack][webhook_url]" type="url"
                        :value="data_get($notificationChannels, 'slack.webhook_url')" placeholder="https://hooks.slack.com/services/..." />
                    <x-input-error :messages="$errors->get('notification_channels.slack.webhook_url')" />

                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @foreach ($eventTypes as $eventType)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="notification_channels[slack][events][{{ $eventType }}]" value="1"
                                    @checked(data_get($notificationChannels, 'slack.events.' . $eventType, false))>
                                <span>{{ __('profile.notification_settings.events.' . $eventType) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.telegram.title') }}</x-heading>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="notification_channels[telegram][enabled]" value="1"
                                @checked(data_get($notificationChannels, 'telegram.enabled', false))>
                            <span>{{ __('profile.notification_settings.enabled') }}</span>
                        </label>
                    </div>

                    <x-paragraph>{{ __('profile.notification_settings.channels.telegram.help') }}</x-paragraph>
                    <div class="grid gap-4 md:grid-cols-2">
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

                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @foreach ($eventTypes as $eventType)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="notification_channels[telegram][events][{{ $eventType }}]" value="1"
                                    @checked(data_get($notificationChannels, 'telegram.events.' . $eventType, false))>
                                <span>{{ __('profile.notification_settings.events.' . $eventType) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.discord.title') }}</x-heading>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="notification_channels[discord][enabled]" value="1"
                                @checked(data_get($notificationChannels, 'discord.enabled', false))>
                            <span>{{ __('profile.notification_settings.enabled') }}</span>
                        </label>
                    </div>

                    <x-paragraph>{{ __('profile.notification_settings.channels.discord.help') }}</x-paragraph>
                    <x-text-input id="notification_channels_discord_webhook_url" name="notification_channels[discord][webhook_url]" type="url"
                        :value="data_get($notificationChannels, 'discord.webhook_url')" placeholder="https://discord.com/api/webhooks/..." />
                    <x-input-error :messages="$errors->get('notification_channels.discord.webhook_url')" />

                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @foreach ($eventTypes as $eventType)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="notification_channels[discord][events][{{ $eventType }}]" value="1"
                                    @checked(data_get($notificationChannels, 'discord.events.' . $eventType, false))>
                                <span>{{ __('profile.notification_settings.events.' . $eventType) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between">
                        <x-heading type="h3">{{ __('profile.notification_settings.channels.webhook.title') }}</x-heading>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="notification_channels[webhook][enabled]" value="1"
                                @checked(data_get($notificationChannels, 'webhook.enabled', false))>
                            <span>{{ __('profile.notification_settings.enabled') }}</span>
                        </label>
                    </div>

                    <x-paragraph>{{ __('profile.notification_settings.channels.webhook.help') }}</x-paragraph>
                    <x-text-input id="notification_channels_webhook_url" name="notification_channels[webhook][url]" type="url"
                        :value="data_get($notificationChannels, 'webhook.url')" placeholder="https://example.com/webhook" />
                    <x-input-error :messages="$errors->get('notification_channels.webhook.url')" />

                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @foreach ($eventTypes as $eventType)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="notification_channels[webhook][events][{{ $eventType }}]" value="1"
                                    @checked(data_get($notificationChannels, 'webhook.events.' . $eventType, false))>
                                <span>{{ __('profile.notification_settings.events.' . $eventType) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <x-primary-button>{{ __('button.update') }}</x-primary-button>
    </form>
</x-container>
