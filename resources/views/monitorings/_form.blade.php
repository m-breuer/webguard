@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringLifecycleStatus;
    use App\Enums\HttpMethod;

    $httpHeadersValue = old('http_headers', isset($monitoring) ? $monitoring->http_headers : null);

    if (is_array($httpHeadersValue)) {
        $httpHeadersValue = json_encode($httpHeadersValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    $dnsExpectedValues = old('dns_expected_values', isset($monitoring) ? $monitoring->dns_expected_values : null);

    if (is_array($dnsExpectedValues)) {
        $dnsExpectedValues = implode(PHP_EOL, $dnsExpectedValues);
    }

    $heartbeatTypeValue = MonitoringType::HEARTBEAT->value;
    $serverHealthTypeValue = MonitoringType::SERVER_HEALTH->value;
    $selectedType = old('type', $monitoring->type->value ?? ($types[0]->value ?? ''));
    $targetPlaceholders = [
        MonitoringType::HTTP->value => __('monitoring.form.placeholders.http_target'),
        MonitoringType::PING->value => __('monitoring.form.placeholders.ping_target'),
        MonitoringType::KEYWORD->value => __('monitoring.form.placeholders.http_target'),
        MonitoringType::PORT->value => __('monitoring.form.placeholders.port_target'),
        MonitoringType::DOMAIN_EXPIRATION->value => __('monitoring.form.placeholders.domain_target'),
        MonitoringType::DNS_RECORD->value => __('monitoring.form.placeholders.dns_target'),
    ];
    $enabledNotificationChannels = $enabledNotificationChannels ?? [];
    $selectedNotificationChannels = old(
        'notification_channels',
        isset($monitoring) ? ($monitoring->notification_channels ?? $enabledNotificationChannels) : $enabledNotificationChannels
    );
    $selectedNotificationChannels = is_array($selectedNotificationChannels) ? $selectedNotificationChannels : [];
    $selectedGroupIds = old(
        'group_ids',
        isset($monitoring) ? $monitoring->groups->pluck('id')->all() : []
    );
    $selectedGroupIds = is_array($selectedGroupIds) ? $selectedGroupIds : [];
    $selectedGroupIds = array_values(array_filter(
        array_map(static fn (mixed $groupId): string => (string) $groupId, $selectedGroupIds),
        static fn (string $groupId): bool => $groupId !== ''
    ));
    $groupOptions = collect($monitoringGroups ?? [])->map(static fn ($monitoringGroup): array => [
        'value' => (string) $monitoringGroup->id,
        'label' => $monitoringGroup->name,
    ])->values();
    $adminTeams = $adminTeams ?? collect();
    $selectedTeamId = old('team_id', $monitoring->team_id ?? '');
    $selectedPreferredLocations = old(
        'preferred_locations',
        old('preferred_location', isset($monitoring) ? $monitoring->preferredLocationCodes() : [$serverInstances->first()?->code])
    );
    $selectedPreferredLocations = is_array($selectedPreferredLocations)
        ? array_values(array_filter(
            array_map(static fn (mixed $location): string => (string) $location, $selectedPreferredLocations),
            static fn (string $location): bool => $location !== ''
        ))
        : array_values(array_filter(
            [(string) $selectedPreferredLocations],
            static fn (string $location): bool => $location !== ''
        ));
    $serverInstanceOptions = collect($serverInstances ?? [])->map(static fn ($serverInstance): array => [
        'value' => $serverInstance->code,
        'label' => $serverInstance->code,
    ])->values();
    $notificationPreferencesFormId = $notificationPreferencesFormId ?? null;
@endphp

@csrf
@if (isset($monitoring))
    @method('PATCH')
@endif

<div x-data="{
    timeoutValue: {{ old('timeout', $monitoring->timeout ?? 5) }},
    publicLabelEnabled: @js(old('public_label_enabled', $monitoring->public_label_enabled ?? false)),
    notificationOnFailure: @js(old('notification_on_failure', $monitoring->notification_on_failure ?? true))
}" data-monitoring-type-form
    @if (isset($monitoring)) data-monitoring-existing @endif
    data-monitoring-target-generated-types="{{ $heartbeatTypeValue }} {{ $serverHealthTypeValue }}"
    data-monitoring-url-types="{{ MonitoringType::HTTP->value }} {{ MonitoringType::KEYWORD->value }}"
    data-monitoring-target-clearing-types="{{ MonitoringType::PING->value }} {{ $heartbeatTypeValue }} {{ $serverHealthTypeValue }} {{ MonitoringType::DOMAIN_EXPIRATION->value }} {{ MonitoringType::DNS_RECORD->value }}"
    data-monitoring-target-placeholders='@json($targetPlaceholders)'>
    <div class="space-y-8">
        <section class="space-y-4">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.basic') }}</x-heading>
            </div>

            <div>
                <x-input-label for="type" :value="__('monitoring.form.type')" />
                @if (isset($monitoring))
                    <x-text-input id="type" class="cursor-not-allowed" name="type" :value="__('monitoring.types.' . $monitoring->type->value)" readonly />
                    <input type="hidden" name="type" value="{{ $selectedType }}">
                @else
                    <x-select-input id="type" class="mt-1 block w-full" name="type" data-monitoring-type-control required
                        :autofocus="! ($modal ?? false)">
                        <option value="" disabled hidden>{{ __('monitoring.form.select_type') }}</option>
                        @foreach ($types as $enumType)
                            <option value="{{ $enumType->value }}" @selected($selectedType === $enumType->value)>
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
            @if ($monitoring->type === MonitoringType::HEARTBEAT || $monitoring->type === MonitoringType::SERVER_HEALTH)
                <x-text-input id="target" type="text" :value="$monitoring->target" readonly disabled
                    class="cursor-not-allowed" />
                <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if ($monitoring->type === MonitoringType::HEARTBEAT)
                    {{ __('monitoring.form.heartbeat_ping_url_help') }}
                    @else
                    {{ __('monitoring.form.server_health_endpoint_help') }}
                    @endif
                </x-paragraph>
            @else
                <x-text-input id="target" type="text" name="target" required
                    placeholder="{{ $targetPlaceholders[$selectedType] ?? '' }}" />
            @endif
        @else
            <div data-monitoring-target-container>
                <x-text-input id="target" type="text" name="target" data-monitoring-target-field required
                    :value="old('target', '')" placeholder="{{ $targetPlaceholders[$selectedType] ?? '' }}" />
            </div>
            <div data-monitoring-type-fields="{{ $heartbeatTypeValue }}"
                class="mt-2 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                {{ __('monitoring.form.heartbeat_target_generated') }}
            </div>
            <div data-monitoring-type-fields="{{ $serverHealthTypeValue }}"
                class="mt-2 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                {{ __('monitoring.form.server_health_target_generated') }}
            </div>
        @endif
        <x-input-error :messages="$errors->get('target')" />
    </div>

        </section>

        <details class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <summary class="group flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                <x-heading type="h2">{{ __('monitoring.form.sections.organization') }}</x-heading>
                <svg class="size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 dark:text-gray-400"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                </svg>
            </summary>

            <div>
                <x-input-label for="team_id" :value="__('team.ownership.select_label')" />
                @if (isset($monitoring))
                    <x-text-input id="team_id" class="cursor-not-allowed" :value="$monitoring->team ? __('team.ownership.team') . ': ' . $monitoring->team->name : __('team.ownership.private')" readonly />
                @else
                    <x-select-input id="team_id" name="team_id" class="mt-1 block w-full">
                        <option value="">{{ __('team.ownership.private') }}</option>
                        @foreach ($adminTeams as $team)
                            <option value="{{ $team->id }}" @selected($selectedTeamId === $team->id)>
                                {{ __('team.ownership.team') }}: {{ $team->name }}
                            </option>
                        @endforeach
                    </x-select-input>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('team.ownership.private_help') }}
                        @if ($adminTeams->isNotEmpty())
                            {{ __('team.ownership.team_help') }}
                        @endif
                    </p>
                @endif
                <x-input-error :messages="$errors->get('team_id')" />
            </div>

            <div>
                <x-input-label for="group_ids" :value="__('monitoring.form.groups')" />
                <x-multi-select
                    id="group_ids"
                    name="group_ids"
                    :options="$groupOptions"
                    :selected="$selectedGroupIds"
                    :placeholder="__('monitoring.form.no_group')"
                    :search-placeholder="__('monitoring.form.search_groups')"
                    :select-all-label="__('monitoring.form.select_all_groups')"
                    :all-selected-label="__('monitoring.form.all_groups_selected')"
                    :no-options-label="__('monitoring.form.no_groups_available')"
                    :no-results-label="__('monitoring.form.no_groups_found')"
                    :remove-label="__('monitoring.form.remove_group')"
                    :clear-label="__('monitoring.form.clear_groups')" />
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('monitoring.form.groups_help') }}
                    @if (!Auth::user()->isDemo())
                        <a href="{{ route('monitoring-groups.index') }}" class="text-purple-700 underline dark:text-purple-300">
                            {{ __('monitoring_group.title') }}
                        </a>
                    @endif
                </p>
                <x-input-error :messages="$errors->get('group_ids')" />
                <x-input-error :messages="$errors->get('group_ids.*')" />
            </div>
        </details>

        <section class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <x-heading type="h2">{{ __('monitoring.form.sections.check') }}</x-heading>
            </div>

    <div data-monitoring-type-fields="{{ MonitoringType::PORT->value }}" class="mt-4">
            <x-input-label for="port" :value="__('monitoring.form.port')" />
            <x-text-input id="port" type="number" name="port" :value="old('port', $monitoring->port ?? '')" />
            <x-input-error :messages="$errors->get('port')" />
    </div>

    <div data-monitoring-type-fields="{{ MonitoringType::KEYWORD->value }}" class="mt-4">
            <x-input-label for="keyword" :value="__('monitoring.form.keyword')" />
            <x-text-input id="keyword" type="text" name="keyword" :value="old('keyword', $monitoring->keyword ?? '')" />
            <x-input-error :messages="$errors->get('keyword')" />
    </div>

    <div data-monitoring-type-fields="{{ MonitoringType::HEARTBEAT->value }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
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

    <div data-monitoring-type-fields="{{ MonitoringType::SERVER_HEALTH->value }}" class="mt-4 space-y-4">
            <div class="rounded-md border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                <p>{{ __('monitoring.form.server_health_help') }}</p>
                <a href="{{ route('scribe') }}" target="_blank" rel="noopener"
                    class="mt-2 inline-block text-purple-800 underline dark:text-purple-400">
                    {{ __('monitoring.form.server_health_docs_link') }}
                </a>
            </div>

            <div>
                <x-heading type="h3" class="text-base">{{ __('monitoring.form.server_health_thresholds') }}</x-heading>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('monitoring.form.server_health_thresholds_help') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="server_health_cpu_threshold_percent" :value="__('monitoring.form.server_health_cpu_threshold_percent')" />
                    <x-text-input id="server_health_cpu_threshold_percent" type="number" min="1" max="100"
                        step="0.01" name="server_health_cpu_threshold_percent" :value="old('server_health_cpu_threshold_percent', isset($monitoring) ? $monitoring->server_health_cpu_threshold_percent : 90)" />
                    <x-input-error :messages="$errors->get('server_health_cpu_threshold_percent')" />
                </div>
                <div>
                    <x-input-label for="server_health_ram_threshold_percent" :value="__('monitoring.form.server_health_ram_threshold_percent')" />
                    <x-text-input id="server_health_ram_threshold_percent" type="number" min="1" max="100"
                        step="0.01" name="server_health_ram_threshold_percent" :value="old('server_health_ram_threshold_percent', isset($monitoring) ? $monitoring->server_health_ram_threshold_percent : 90)" />
                    <x-input-error :messages="$errors->get('server_health_ram_threshold_percent')" />
                </div>
                <div>
                    <x-input-label for="server_health_load_threshold_per_cpu" :value="__('monitoring.form.server_health_load_threshold_per_cpu')" />
                    <x-text-input id="server_health_load_threshold_per_cpu" type="number" min="0.01" max="100"
                        step="0.01" name="server_health_load_threshold_per_cpu" :value="old('server_health_load_threshold_per_cpu', isset($monitoring) ? $monitoring->server_health_load_threshold_per_cpu : '')" />
                    <x-input-error :messages="$errors->get('server_health_load_threshold_per_cpu')" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="server_health_service_response_time_threshold_ms" :value="__('monitoring.form.server_health_service_response_time_threshold_ms')" />
                    <x-text-input id="server_health_service_response_time_threshold_ms" type="number" min="1" max="60000"
                        name="server_health_service_response_time_threshold_ms" :value="old('server_health_service_response_time_threshold_ms', isset($monitoring) ? $monitoring->server_health_service_response_time_threshold_ms : '')" />
                    <x-input-error :messages="$errors->get('server_health_service_response_time_threshold_ms')" />
                </div>
                <div>
                    <x-input-label for="server_health_report_interval_minutes" :value="__('monitoring.form.server_health_report_interval_minutes')" />
                    <x-text-input id="server_health_report_interval_minutes" type="number" min="1" max="1440"
                        name="server_health_report_interval_minutes" :value="old('server_health_report_interval_minutes', isset($monitoring) ? $monitoring->server_health_report_interval_minutes : 1)" />
                    <x-input-error :messages="$errors->get('server_health_report_interval_minutes')" />
                </div>
                <div>
                    <x-input-label for="server_health_grace_minutes" :value="__('monitoring.form.server_health_grace_minutes')" />
                    <x-text-input id="server_health_grace_minutes" type="number" min="0" max="1440"
                        name="server_health_grace_minutes" :value="old('server_health_grace_minutes', isset($monitoring) ? $monitoring->server_health_grace_minutes : 5)" />
                    <x-input-error :messages="$errors->get('server_health_grace_minutes')" />
                </div>
            </div>
    </div>

    <div data-monitoring-type-fields="{{ MonitoringType::DNS_RECORD->value }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="dns_record_type" :value="__('monitoring.form.dns_record_type')" />
                <x-select-input id="dns_record_type" class="mt-1 block w-full" name="dns_record_type">
                    @foreach (\App\Support\DnsRecordExpectation::recordTypes() as $recordType)
                        <option value="{{ $recordType }}" @selected(old('dns_record_type', $monitoring->dns_record_type ?? 'A') === $recordType)>
                            {{ $recordType }}
                        </option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('dns_record_type')" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="dns_expected_values" :value="__('monitoring.form.dns_expected_values')" />
                <x-textarea id="dns_expected_values" name="dns_expected_values" rows="5"
                    placeholder="{{ __('monitoring.form.placeholders.dns_expected_values') }}">{{ $dnsExpectedValues ?? '' }}</x-textarea>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('monitoring.form.dns_expected_values_help') }}
                </p>
                <x-input-error :messages="$errors->get('dns_expected_values')" />
            </div>
    </div>

    <div data-monitoring-type-fields="{{ MonitoringType::HTTP->value }} {{ MonitoringType::KEYWORD->value }}" class="mt-4">
            <x-input-label for="timeout" :value="__('monitoring.form.timeout')" />
            <input id="timeout" name="timeout" type="range" min="1" max="60" step="1"
                class="w-full accent-purple-600" x-model="timeoutValue" />
            <div class="mt-1 font-semibold text-purple-600">
                <x-span>{{ __('monitoring.form.timeout_selected') }}</x-span>
                <x-span x-text="timeoutValue + 's'"></x-span>
            </div>
            <x-input-error :messages="$errors->get('timeout')" />
    </div>

    <div data-monitoring-type-fields="{{ MonitoringType::HTTP->value }} {{ MonitoringType::KEYWORD->value }}" class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="response_time_threshold_ms" :value="__('monitoring.form.response_time_threshold_ms')" />
                <x-text-input id="response_time_threshold_ms" type="number" min="1" max="60000" name="response_time_threshold_ms" :value="old('response_time_threshold_ms', isset($monitoring) ? $monitoring->response_time_threshold_ms : null)" class="mt-1 block w-full" />
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('monitoring.form.response_time_threshold_ms_help') }}</p>
                <x-input-error :messages="$errors->get('response_time_threshold_ms')" />
            </div>
            <div>
                <x-input-label for="response_time_confirmation_threshold" :value="__('monitoring.form.response_time_confirmation_threshold')" />
                <x-text-input id="response_time_confirmation_threshold" type="number" min="1" max="10" name="response_time_confirmation_threshold" :value="old('response_time_confirmation_threshold', isset($monitoring) ? $monitoring->response_time_confirmation_threshold : null)" class="mt-1 block w-full" />
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('monitoring.form.response_time_confirmation_threshold_help') }}</p>
                <x-input-error :messages="$errors->get('response_time_confirmation_threshold')" />
            </div>
    </div>

    <div data-monitoring-type-fields="{{ MonitoringType::HTTP->value }} {{ MonitoringType::KEYWORD->value }}" class="mt-4">
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

    <div data-monitoring-type-fields="{{ MonitoringType::HTTP->value }} {{ MonitoringType::KEYWORD->value }}" class="mt-4">
            <x-input-label for="expected_http_statuses" :value="__('monitoring.form.expected_http_statuses')" />
            <x-text-input id="expected_http_statuses" type="text" name="expected_http_statuses" :value="old('expected_http_statuses', $monitoring->expected_http_statuses ?? '200-299')"
                placeholder="{{ __('monitoring.form.placeholders.expected_http_statuses') }}" />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('monitoring.form.expected_http_statuses_help') }}
            </p>
            <x-input-error :messages="$errors->get('expected_http_statuses')" />
    </div>

    <details data-monitoring-type-fields="{{ MonitoringType::HTTP->value }} {{ MonitoringType::KEYWORD->value }}"
        class="mt-4 rounded-md border border-gray-200 p-4 dark:border-gray-700">
        <summary class="group flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-gray-800 [&::-webkit-details-marker]:hidden dark:text-gray-100">
            <span>{{ __('monitoring.form.advanced_request_settings') }}</span>
            <svg class="size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 dark:text-gray-400"
                viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25-4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1-1.08 1.06Z" clip-rule="evenodd" />
            <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
            </svg>
        </summary>

    <div class="mt-4">
            <x-input-label for="auth_username" :value="__('monitoring.form.auth_username')" />
            <x-text-input id="auth_username" type="text" name="auth_username" :value="old('auth_username', $monitoring->auth_username ?? '')" />
            <x-input-error :messages="$errors->get('auth_username')" />

            <x-input-label for="auth_password" :value="__('monitoring.form.auth_password')" class="mt-4" />
            <x-text-input id="auth_password" type="password" name="auth_password" :value="old('auth_password', $monitoring->auth_password ?? '')" />
            <x-input-error :messages="$errors->get('auth_password')" />
    </div>

    <div class="mt-4">
            <x-input-label for="http_headers" :value="__('monitoring.form.http_headers')" />
            <x-textarea id="http_headers" type="text" name="http_headers" rows="4"
                placeholder="{{ __('monitoring.form.placeholders.http_headers') }}">{{ $httpHeadersValue ?? '' }}</x-textarea>
            <x-input-error :messages="$errors->get('http_headers')" />
    </div>

    <div class="mt-4">
            <x-input-label for="http_body" :value="__('monitoring.form.http_body')" />
            <x-textarea id="http_body" name="http_body" rows="4"
                placeholder="{{ __('monitoring.form.placeholders.http_body') }}">{{ old('http_body', $monitoring->http_body ?? '') }}</x-textarea>
            <x-input-error :messages="$errors->get('http_body')" />
    </div>

    </details>

        </section>

        <details class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <summary class="group flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                <x-heading type="h2">{{ __('monitoring.form.sections.sharing') }}</x-heading>
                <svg class="size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 dark:text-gray-400"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
            </summary>

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
                    <x-input-label for="sla-badge-snippet" :value="__('monitoring.detail.sla_badge.heading')" />
                    <x-paragraph
                        class="text-sm text-gray-600 dark:text-gray-400">{{ __('monitoring.detail.sla_badge.description') }}</x-paragraph>
                    <x-paragraph
                        class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('monitoring.detail.sla_badge.snippet_help') }}</x-paragraph>
                    <div class="mt-2 flex items-center space-x-2">
                        <pre id="sla-badge-snippet"
                            class="flex-grow overflow-auto rounded-md border-gray-300 bg-gray-100 p-2 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"><code>&lt;div data-webguard-sla-badge data-monitoring="{{ $monitoring->id }}" data-range="90" data-theme="auto" data-size="compact"&gt;&lt;/div&gt;
&lt;script src="{{ route('badge.js') }}"&gt;&lt;/script&gt;</code></pre>
                    </div>
                </div>
            </div>
        @endif
    </div>

        </details>

        <details class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <summary class="group flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                <x-heading type="h2">{{ __('monitoring.form.sections.notifications') }}</x-heading>
                <svg class="size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 dark:text-gray-400"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 1 1-1.08 1.06Z" clip-rule="evenodd" />
                </svg>
            </summary>

    @if (isset($monitoring) && isset($notificationPreference) && $notificationPreferencesFormId)
        @include('monitorings._notification_preferences', [
            'embedded' => true,
            'formId' => $notificationPreferencesFormId,
            'fieldIdPrefix' => $fieldIdPrefix ?? 'monitoring_notification_preference',
        ])
    @endif

    @unless (isset($monitoring))
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
    @endunless

    <div class="mt-4">
        <x-input-label for="failure_confirmation_threshold" :value="__('monitoring.form.failure_confirmation_threshold')" />
        <x-text-input id="failure_confirmation_threshold" type="number" min="1" max="10"
            name="failure_confirmation_threshold" :value="old('failure_confirmation_threshold', $monitoring->failure_confirmation_threshold ?? 1)" />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('monitoring.form.failure_confirmation_threshold_help') }}
        </p>
        <x-input-error :messages="$errors->get('failure_confirmation_threshold')" />
    </div>

    @unless (isset($monitoring))
    <div class="mt-4">
        <x-input-label for="notification_channels" :value="__('monitoring.form.notification_channels')" />
        @if (count($enabledNotificationChannels) > 0)
            <x-select-input id="notification_channels" name="notification_channels[]" multiple
                size="{{ min(4, count($enabledNotificationChannels)) }}" class="mt-1 block w-full">
                @foreach ($enabledNotificationChannels as $channel)
                    <option value="{{ $channel }}" @selected(in_array($channel, $selectedNotificationChannels, true))>
                        {{ __('profile.notification_settings.channels.' . $channel . '.title') }}
                    </option>
                @endforeach
            </x-select-input>
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
    @endunless

        </details>

        <details class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <summary class="group flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                <x-heading type="h2">{{ __('monitoring.form.sections.operations') }}</x-heading>
                <svg class="size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 dark:text-gray-400"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 1 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
            </summary>

    <div class="mt-4">
        <x-input-label for="preferred_locations" :value="__('monitoring.form.preferred_location')" />
        <x-multi-select
            id="preferred_locations"
            name="preferred_locations"
            class="mt-1"
            :options="$serverInstanceOptions"
            :selected="$selectedPreferredLocations"
            :placeholder="__('monitoring.form.no_preferred_locations')"
            :search-placeholder="__('monitoring.form.search_preferred_locations')"
            :select-all-label="__('monitoring.form.select_all_preferred_locations')"
            :all-selected-label="__('monitoring.form.all_preferred_locations_selected')"
            :no-options-label="__('monitoring.form.no_preferred_locations_available')"
            :no-results-label="__('monitoring.form.no_preferred_locations_found')"
            :remove-label="__('monitoring.form.remove_preferred_location')"
            :clear-label="__('monitoring.form.clear_preferred_locations')" />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('monitoring.form.preferred_locations_help') }}
        </p>
        <x-input-error :messages="$errors->get('preferred_location')" />
        <x-input-error :messages="$errors->get('preferred_locations')" />
        <x-input-error :messages="$errors->get('preferred_locations.*')" />
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

        </details>

        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 pt-6 dark:border-gray-700"
            data-monitoring-form-actions>
            <x-secondary-button
                :href="isset($modal) && $modal ? null : (isset($monitoring) ? route('monitorings.show', $monitoring) : route('monitorings.index'))"
                type="button"
                x-on:click="$dispatch('close-form-modal', 'monitoring-form-modal')"
            >
                {{ __('button.cancel') }}
            </x-secondary-button>
            <x-primary-button>{{ isset($monitoring) ? __('button.update') : __('button.create') }}</x-primary-button>
        </div>
    </div>
</div>
