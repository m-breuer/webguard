@php
    $selectedNotificationChannels = $notificationPreference->notification_channels ?? [];
    $selectedNotificationChannels = is_array($selectedNotificationChannels) ? $selectedNotificationChannels : [];
    $enabledNotificationChannels = $enabledNotificationChannels ?? Auth::user()->enabledNotificationChannelKeys();
    $fieldIdPrefix = $fieldIdPrefix ?? 'monitoring_notification_preference';
@endphp

<section class="rounded-lg border border-gray-200 bg-gray-50/70 p-4 shadow-none dark:border-gray-700 dark:bg-gray-900/30 sm:p-5"
    data-monitoring-notification-preferences>
    <x-heading type="h3">{{ __('team.sections.notification_preferences') }}</x-heading>
    <form method="POST" action="{{ route('monitorings.notification-preferences.update', $monitoring) }}"
        class="mt-4 space-y-4">
        @csrf
        @method('PATCH')

        <x-text-checkbox id="{{ $fieldIdPrefix }}_notification_on_failure" name="notification_on_failure" value="1"
            :checked="$notificationPreference->notification_on_failure"
            label="{{ __('monitoring.form.notification_on_failure_enabled') }}" />

        <div>
            <x-input-label for="{{ $fieldIdPrefix }}_notification_channels" :value="__('monitoring.form.notification_channels')" />
            @if ($enabledNotificationChannels === [])
                <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('monitoring.form.notification_channels_empty') }}
                </x-paragraph>
            @else
                <div id="{{ $fieldIdPrefix }}_notification_channels" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($enabledNotificationChannels as $channel)
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                            <input type="checkbox" name="notification_channels[]" value="{{ $channel }}"
                                class="rounded-sm border-gray-300 text-purple-600 shadow-xs focus:border-purple-300 focus:ring-3 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
                                @checked(in_array($channel, $selectedNotificationChannels, true))>
                            <span>{{ __('profile.notification_settings.channels.' . $channel . '.title') }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
            <x-input-error :messages="$errors->get('notification_channels')" />
            <x-input-error :messages="$errors->get('notification_channels.*')" />
        </div>

        <div>
            <x-input-label for="{{ $fieldIdPrefix }}_ssl_expiry_warning_days" :value="__('monitoring.form.ssl_expiry_warning_days')" />
            <x-text-input id="{{ $fieldIdPrefix }}_ssl_expiry_warning_days" type="number" min="1" max="365"
                name="ssl_expiry_warning_days" :value="old('ssl_expiry_warning_days', $notificationPreference->ssl_expiry_warning_days)" required />
            <x-input-error :messages="$errors->get('ssl_expiry_warning_days')" />
        </div>

        <x-secondary-button>
            {{ __('button.save') }}
        </x-secondary-button>
    </form>
</section>
