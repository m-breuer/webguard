@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringLifecycleStatus;
    use App\Enums\HttpMethod;

    $httpHeadersValue = old('http_headers', isset($monitoring) ? $monitoring->http_headers : null);

    if (is_array($httpHeadersValue)) {
        $httpHeadersValue = json_encode($httpHeadersValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    $heartbeatTypeValue = MonitoringType::HEARTBEAT->value;
    $enabledNotificationChannels = $enabledNotificationChannels ?? [];
    $selectedNotificationChannels = old(
        'notification_channels',
        isset($monitoring) ? ($monitoring->notification_channels ?? $enabledNotificationChannels) : $enabledNotificationChannels
    );
    $selectedNotificationChannels = is_array($selectedNotificationChannels) ? $selectedNotificationChannels : [];
@endphp

@csrf
@if (isset($monitoring))
    @method('PATCH')
@endif

<div x-data="{
    type: @js(old('type', $monitoring->type->value ?? ($types[0]->value ?? ''))),
    target: @js(old('target', $monitoring->target ?? '')),
    timeoutValue: {{ old('timeout', $monitoring->timeout ?? 5) }},
    publicLabelEnabled: @js(old('public_label_enabled', $monitoring->public_label_enabled ?? false)),
    notificationOnFailure: @js(old('notification_on_failure', $monitoring->notification_on_failure ?? true)),
    init() {
        if (!@js(isset($monitoring))) {
            if ((this.type === '{{ MonitoringType::HTTP->value }}' || this.type === '{{ MonitoringType::KEYWORD->value }}') && (!this.target || !this.target.startsWith('http'))) {
                this.target = 'https://';
            } else if (this.type === '{{ MonitoringType::PING->value }}' || this.type === '{{ MonitoringType::PORT->value }}' || this.type === '{{ MonitoringType::DOMAIN_EXPIRATION->value }}') {
                this.target = '';
            } else if (this.type === '{{ MonitoringType::HEARTBEAT->value }}') {
                this.target = '';
            }
        }
    }
}" x-init="$watch('type', value => {
    if ((value === '{{ MonitoringType::HTTP->value }}' || value === '{{ MonitoringType::KEYWORD->value }}') && (!target || !target.startsWith('http'))) {
        target = 'https://';
    } else if (value === '{{ MonitoringType::PING->value }}' || value === '{{ MonitoringType::HEARTBEAT->value }}' || value === '{{ MonitoringType::DOMAIN_EXPIRATION->value }}') {
        target = '';
    }
})">
    <div class="space-y-8">
        <section class="space-y-4">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.basic') }}</x-heading>
            </div>

            <div>
                <x-input-label for="type" :value="__('monitoring.form.type')" />
                @if (isset($monitoring))
                    <x-text-input id="type" class="cursor-not-allowed" name="type" :value="__('monitoring.types.' . $monitoring->type->value)" readonly />
                    <input type="hidden" name="type" :value="type">
                @else
                    <x-select-input id="type" class="mt-1 block w-full" name="type" x-model="type" required autofocus>
                        <option value="" disabled hidden>{{ __('monitoring.form.select_type') }}</option>
                        @foreach ($types as $enumType)
                            <option value="{{ $enumType->value }}" @selected(old('type') === $enumType->value)>
                                {{ __('monitoring.types.' . $enumType->value) }}
                            </option>
                        @endforeach
                    </x-select-input>
                @endif
                <x-input-error :messages="$errors->get('type')" />
            </div>

    <div class="mt-4">
        <x-input-label for="name" :value="__('monitoring.form.name')" />
        <x-text-input id="name" type="text" name="name" :value="old('name', $monitoring->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="mt-4">
        <x-input-label for="target" :value="__('monitoring.form.target')" />
        @if (isset($monitoring))
            <x-text-input id="target" type="text" :value="$monitoring->target" readonly disabled
                class="cursor-not-allowed" />
            <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ $monitoring->type === MonitoringType::HEARTBEAT ? __('monitoring.form.heartbeat_ping_url_help') : __('monitoring.form.target_immutable_help') }}
            </x-paragraph>
        @else
            <div x-show="type !== '{{ $heartbeatTypeValue }}'">
                <x-text-input id="target" type="text" name="target" x-model="target"
                    x-bind:required="type !== '{{ $heartbeatTypeValue }}'"
                    x-bind:placeholder="type === '{{ MonitoringType::HTTP->value }}' ? '{{ __('monitoring.form.placeholders.http_target') }}' :
                    type === '{{ MonitoringType::PING->value }}' ?
                    '{{ __('monitoring.form.placeholders.ping_target') }}' :
                    type === '{{ MonitoringType::KEYWORD->value }}' ?
                    '{{ __('monitoring.form.placeholders.http_target') }}' :
                    type === '{{ MonitoringType::PORT->value }}' ?
                    '{{ __('monitoring.form.placeholders.port_target') }}' :
                    type === '{{ MonitoringType::DOMAIN_EXPIRATION->value }}' ?
                    '{{ __('monitoring.form.placeholders.domain_target') }}' : ''" />
            </div>
            <div x-show="type === '{{ $heartbeatTypeValue }}'"
                class="mt-2 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                {{ __('monitoring.form.heartbeat_target_generated') }}
            </div>
            <x-input-error :messages="$errors->get('target')" />
        @endif
    </div>

        </section>

        <section class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.check') }}</x-heading>
            </div>

    <template x-if="type === '{{ MonitoringType::PORT->value }}'">
        <div class="mt-4">
            <x-input-label for="port" :value="__('monitoring.form.port')" />
            <x-text-input id="port" type="number" name="port" :value="old('port', $monitoring->port ?? '')" />
            <x-input-error :messages="$errors->get('port')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="keyword" :value="__('monitoring.form.keyword')" />
            <x-text-input id="keyword" type="text" name="keyword" :value="old('keyword', $monitoring->keyword ?? '')" />
            <x-input-error :messages="$errors->get('keyword')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HEARTBEAT->value }}'">
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="heartbeat_interval_minutes" :value="__('monitoring.form.heartbeat_interval_minutes')" />
                <x-text-input id="heartbeat_interval_minutes" type="number" min="1" max="10080"
                    name="heartbeat_interval_minutes" :value="old('heartbeat_interval_minutes', $monitoring->heartbeat_interval_minutes ?? 60)" />
                <x-input-error :messages="$errors->get('heartbeat_interval_minutes')" />
            </div>
            <div>
                <x-input-label for="heartbeat_grace_minutes" :value="__('monitoring.form.heartbeat_grace_minutes')" />
                <x-text-input id="heartbeat_grace_minutes" type="number" min="0" max="1440"
                    name="heartbeat_grace_minutes" :value="old('heartbeat_grace_minutes', $monitoring->heartbeat_grace_minutes ?? 5)" />
                <x-input-error :messages="$errors->get('heartbeat_grace_minutes')" />
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 md:col-span-2">
                {{ __('monitoring.form.heartbeat_help') }}
            </p>
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HTTP->value }}' || type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="timeout" :value="__('monitoring.form.timeout')" />
            <input id="timeout" name="timeout" type="range" min="1" max="60" step="1"
                class="w-full accent-purple-600" x-model="timeoutValue" />
            <div class="mt-1 font-semibold text-purple-600">
                <x-span>{{ __('monitoring.form.timeout_selected') }}</x-span>
                <x-span x-text="timeoutValue + 's'"></x-span>
            </div>
            <x-input-error :messages="$errors->get('timeout')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HTTP->value }}' || type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="http_method" :value="__('monitoring.form.http_method')" />
            <x-select-input id="http_method" class="mt-1 block w-full" name="http_method">
                @foreach (HttpMethod::cases() as $method)
                    <option value="{{ $method->value }}" @selected(old('http_method', $monitoring->http_method?->value ?? 'GET') === $method->value)>
                        {{ strtoupper($method->value) }}
                    </option>
                @endforeach
            </x-select-input>
            <x-input-error :messages="$errors->get('http_method')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HTTP->value }}' || type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="expected_http_statuses" :value="__('monitoring.form.expected_http_statuses')" />
            <x-text-input id="expected_http_statuses" type="text" name="expected_http_statuses" :value="old('expected_http_statuses', $monitoring->expected_http_statuses ?? '200-299')"
                placeholder="{{ __('monitoring.form.placeholders.expected_http_statuses') }}" />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('monitoring.form.expected_http_statuses_help') }}
            </p>
            <x-input-error :messages="$errors->get('expected_http_statuses')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HTTP->value }}' || type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="auth_username" :value="__('monitoring.form.auth_username')" />
            <x-text-input id="auth_username" type="text" name="auth_username" :value="old('auth_username', $monitoring->auth_username ?? '')" />
            <x-input-error :messages="$errors->get('auth_username')" />

            <x-input-label for="auth_password" :value="__('monitoring.form.auth_password')" class="mt-4" />
            <x-text-input id="auth_password" type="password" name="auth_password" :value="old('auth_password', $monitoring->auth_password ?? '')" />
            <x-input-error :messages="$errors->get('auth_password')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HTTP->value }}' || type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="http_headers" :value="__('monitoring.form.http_headers')" />
            <x-textarea id="http_headers" type="text" name="http_headers" rows="4"
                placeholder="{{ __('monitoring.form.placeholders.http_headers') }}">{{ $httpHeadersValue ?? '' }}</x-textarea>
            <x-input-error :messages="$errors->get('http_headers')" />
        </div>
    </template>

    <template x-if="type === '{{ MonitoringType::HTTP->value }}' || type === '{{ MonitoringType::KEYWORD->value }}'">
        <div class="mt-4">
            <x-input-label for="http_body" :value="__('monitoring.form.http_body')" />
            <x-textarea id="http_body" name="http_body" rows="4"
                placeholder="{{ __('monitoring.form.placeholders.http_body') }}">{{ old('http_body', $monitoring->http_body ?? '') }}</x-textarea>
            <x-input-error :messages="$errors->get('http_body')" />
        </div>
    </template>

        </section>

        <section class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.sharing') }}</x-heading>
            </div>

    <div class="mt-4">
        <x-input-label for="public_label_enabled" :value="__('monitoring.form.public_label')" />
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox" name="public_label_enabled" value="1" class="peer sr-only"
                x-model="publicLabelEnabled" @if (old('public_label_enabled', $monitoring->public_label_enabled ?? false)) checked @endif>
            <div
                class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-purple-800">
            </div>
            <span
                class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __('monitoring.form.public_label_enabled') }}</span>
        </label>
        @if (isset($monitoring))
            <div x-show="publicLabelEnabled" x-transition>
                <div class="mt-2">
                    <x-input-label for="public_label_url" :value="__('monitoring.form.public_label_url')" />
                    <x-text-input id="public_label_url" type="text" :value="route('public-label', $monitoring->id)" readonly />
                </div>

                <div class="mt-4">
                    <x-input-label for="widget-snippet" :value="__('monitoring.detail.widget.heading')" />
                    <x-paragraph
                        class="text-sm text-gray-600 dark:text-gray-400">{{ __('monitoring.detail.widget.description') }}</x-paragraph>
                    <div class="mt-2 flex items-center space-x-2">
                        <pre id="widget-snippet"
                            class="flex-grow overflow-auto rounded-md border-gray-300 bg-gray-100 p-2 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"><code>&lt;div id="webguard-widget" data-monitoring="{{ $monitoring->id }}"&gt;&lt;/div&gt;
&lt;script src="{{ route('widget.js') }}"&gt;&lt;/script&gt;</code></pre>
                    </div>
                </div>
            </div>
        @endif
    </div>

        </section>

        <section class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.notifications') }}</x-heading>
            </div>

    <div class="mt-4">
        <x-input-label for="notification_on_failure" :value="__('monitoring.form.notification_on_failure')" />
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox" name="notification_on_failure" value="1" class="peer sr-only"
                x-model="notificationOnFailure" @if (old('notification_on_failure', $monitoring->notification_on_failure ?? true)) checked @endif>
            <div
                class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-purple-800">
            </div>
            <span
                class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __('monitoring.form.notification_on_failure_enabled') }}</span>
        </label>
    </div>

    <div class="mt-4">
        <x-input-label for="notification_channels" :value="__('monitoring.form.notification_channels')" />
        @if (count($enabledNotificationChannels) > 0)
            <select id="notification_channels" name="notification_channels[]" multiple size="{{ min(4, count($enabledNotificationChannels)) }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                @foreach ($enabledNotificationChannels as $channel)
                    <option value="{{ $channel }}" @selected(in_array($channel, $selectedNotificationChannels, true))>
                        {{ __('profile.notification_settings.channels.' . $channel . '.title') }}
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('monitoring.form.notification_channels_help') }}
            </p>
        @else
            <p class="mt-2 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                {{ __('monitoring.form.notification_channels_empty') }}
            </p>
        @endif
        <x-input-error :messages="$errors->get('notification_channels')" />
        <x-input-error :messages="$errors->get('notification_channels.*')" />
    </div>

    <div class="mt-4">
        <x-input-label for="ssl_expiry_warning_days" :value="__('monitoring.form.ssl_expiry_warning_days')" />
        <x-text-input id="ssl_expiry_warning_days" type="number" min="1" max="365" name="ssl_expiry_warning_days"
            :value="old('ssl_expiry_warning_days', $monitoring->ssl_expiry_warning_days ?? 7)" />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('monitoring.form.ssl_expiry_warning_days_help') }}
        </p>
        <x-input-error :messages="$errors->get('ssl_expiry_warning_days')" />
    </div>

        </section>

        <section class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.operations') }}</x-heading>
            </div>

    <div class="mt-4">
        <x-input-label for="preferred_location" :value="__('monitoring.form.preferred_location')" />
        <x-select-input id="preferred_location" class="mt-1 block w-full" name="preferred_location" required>
            @foreach ($serverInstances as $instance)
                <option value="{{ $instance->code }}" @selected(old('preferred_location', $monitoring->preferred_location ?? $serverInstances->first()?->code) === $instance->code)>
                    {{ $instance->code }}
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('preferred_location')" />
    </div>

    <div class="mt-4">
        <x-input-label for="status" :value="__('monitoring.form.status')" />
        <x-select-input id="status" class="mt-1 block w-full" name="status" required>
            @foreach (MonitoringLifecycleStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $monitoring->status?->value ?? 'active') === $status->value)>
                    {{ ucfirst($status->value) }}
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('status')" />
    </div>

    <div class="mt-4">
        <x-input-label for="maintenance_from" :value="__('monitoring.form.maintenance_from')" />
        <x-text-input id="maintenance_from" type="datetime-local" name="maintenance_from" :value="old('maintenance_from', isset($monitoring) ? $monitoring->maintenance_from?->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('maintenance_from')" />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('monitoring.form.maintenance_help') }}
        </p>
    </div>

    <div class="mt-4">
        <x-input-label for="maintenance_until" :value="__('monitoring.form.maintenance_until')" />
        <x-text-input id="maintenance_until" type="datetime-local" name="maintenance_until" :value="old('maintenance_until', isset($monitoring) ? $monitoring->maintenance_until?->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('maintenance_until')" />
    </div>

        </section>

        <x-primary-button>{{ isset($monitoring) ? __('button.update') : __('button.create') }}</x-primary-button>
    </div>
</div>
