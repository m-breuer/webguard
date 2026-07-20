@php
    $selectedNotificationChannels = $notificationPreference->notification_channels ?? [];
    $selectedNotificationChannels = is_array($selectedNotificationChannels) ? $selectedNotificationChannels : [];
    $enabledNotificationChannels = $enabledNotificationChannels ?? Auth::user()->enabledNotificationChannelKeys();
    $fieldIdPrefix = $fieldIdPrefix ?? 'monitoring_notification_preference';
    $embedded = $embedded ?? false;
    $formId = $formId ?? null;
@endphp

<section class="rounded-lg border border-gray-200 bg-gray-50/70 p-4 shadow-none dark:border-gray-700 dark:bg-gray-900/30 sm:p-5"
    data-monitoring-notification-preferences>
    <x-heading type="h3">{{ __('team.sections.notification_preferences') }}</x-heading>
    @unless ($embedded)
        <form method="POST" action="{{ route('monitorings.notification-preferences.update', $monitoring) }}"
            class="mt-4 space-y-4">
            @csrf
            @method('PATCH')
    @endunless

    <div @class(['mt-4 space-y-4' => $embedded, 'space-y-4' => ! $embedded])>
        @if ($embedded)
            <label for="{{ $fieldIdPrefix }}_notification_on_failure" class="inline-flex items-center">
                <input id="{{ $fieldIdPrefix }}_notification_on_failure" name="notification_on_failure" type="checkbox"
                    value="1" form="{{ $formId }}"
                    class="shadow-xs focus:ring-3 rounded-sm border-gray-300 text-purple-600 focus:border-purple-300 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
                    @checked($notificationPreference->notification_on_failure)>
                <x-span class="ms-2 text-gray-600 dark:text-gray-300">
                    {{ __('monitoring.form.notification_on_failure_enabled') }}
                </x-span>
            </label>
        @else
            <x-text-checkbox id="{{ $fieldIdPrefix }}_notification_on_failure" name="notification_on_failure" value="1"
                :checked="$notificationPreference->notification_on_failure"
                label="{{ __('monitoring.form.notification_on_failure_enabled') }}" />
        @endif

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
                                @if ($embedded) form="{{ $formId }}" @endif
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
            <input id="{{ $fieldIdPrefix }}_ssl_expiry_warning_days" type="number" min="1" max="365"
                name="ssl_expiry_warning_days" value="{{ old('ssl_expiry_warning_days', $notificationPreference->ssl_expiry_warning_days) }}"
                @if ($embedded) form="{{ $formId }}" @endif
                class="mt-1 w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                required>
            <x-input-error :messages="$errors->get('ssl_expiry_warning_days')" />
        </div>

        @if ($embedded)
            <x-secondary-button form="{{ $formId }}">
                {{ __('button.save') }}
            </x-secondary-button>
        @else
            <x-secondary-button>
                {{ __('button.save') }}
            </x-secondary-button>
        @endif
    </div>
    @unless ($embedded)
        </form>
    @endunless
</section>
