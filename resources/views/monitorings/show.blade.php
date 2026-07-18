@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1" class="flex flex-wrap items-baseline">
            {{ $monitoring->name }}:
            <x-span class="ml-2 {{ $monitoring->isHeartbeat() ? 'break-all text-sm sm:text-base' : '' }}">{{ $monitoring->target }}</x-span>
            <x-span class="ml-2 text-gray-500">({{ strtoupper($monitoring->type->value) }})</x-span>
            @if ($monitoring->public_label_enabled)
                <a href="{{ route('public-label', $monitoring) }}" target="_blank"
                    class="ml-2 text-gray-400 hover:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-4.5 0V6.75a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-.75.75H13.5a.75.75 0 0 1-.75-.75Z" />
                    </svg>
                </a>
            @endif
            @if ($monitoring->isPaused())
                <x-badge type="warning">
                    {{ __('monitoring.index.table.paused') }}
                </x-badge>
            @endif
            @if ($monitoring->isUnderMaintenance())
                <x-badge type="info">
                    {{ __('monitoring.index.table.maintenance') }}
                </x-badge>
            @endif
        </x-heading>

        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}" class="ml-auto flex flex-wrap items-start gap-2 sm:items-center">

            @if ($canManageMonitoring)
                <div class="relative" x-data="{ open: false }">
                    <x-secondary-button @click="open = !open">
                        {{ __('monitoring.actions.heading') }}
                    </x-secondary-button>

                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute z-10 mt-2 min-w-full rounded-md bg-white shadow-lg" style="display: none">
                        <a href="{{ route('monitorings.edit', ['monitoring' => $monitoring->id]) }}"
                            data-form-modal-trigger data-form-modal-name="monitoring-form-modal"
                            class="block px-4 py-2 text-left text-gray-700 hover:bg-gray-100 sm:text-right">
                            {{ __('monitoring.actions.edit') }}
                        </a>
                        <form method="POST" action="{{ route('monitorings.destroyResults', $monitoring) }}"
                            data-confirm-message="{{ __('monitoring.actions.reset.confirmation') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="block w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 sm:text-right">
                                {{ __('monitoring.actions.reset.heading') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('monitorings.destroy', $monitoring) }}"
                            data-confirm-message="{{ __('monitoring.actions.delete.confirmation') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="block w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 sm:text-right">
                                {{ __('monitoring.actions.delete.heading') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <x-secondary-button :href="route('monitorings.index')">
                {{ __('button.back') }}
            </x-secondary-button>

            <x-form-modal name="monitoring-form-modal" title="{{ __('monitoring.title') }}"
                description="{{ __('monitoring.form.sections.basic') }}" max-width="6xl">
                <div class="p-6" x-ref="content">
                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>
    </x-slot>


    <x-main x-init="loadStatus();
    loadHeatmap();
    loadUptime();
    loadPerformanceChart(responseTimeRange);
    loadIncidents(incidentsRange);
    loadChecks();
    initializeDeferredLoads();" x-data="monitoringDetail('{{ $monitoring->id }}', {
        min: '{{ __('monitoring.detail.response_time.min_label') }}',
        avg: '{{ __('monitoring.detail.response_time.avg_label') }}',
        max: '{{ __('monitoring.detail.response_time.max_label') }}',
        yAxis: '{{ __('monitoring.detail.response_time.y_axis_label') }}',
        xAxis: '{{ __('monitoring.detail.response_time.x_axis_label') }}',
        checkStatusSuccess: '{{ __('monitoring.detail.checks.statuses.success') }}',
        checkStatusRedirect: '{{ __('monitoring.detail.checks.statuses.redirect') }}',
        checkStatusClientError: '{{ __('monitoring.detail.checks.statuses.client_error') }}',
        checkStatusServerError: '{{ __('monitoring.detail.checks.statuses.server_error') }}',
        checkStatusUnknown: '{{ __('monitoring.detail.checks.statuses.unknown') }}',
        checkStatusMaintenance: '{{ __('monitoring.detail.checks.statuses.maintenance') }}',
        checkSourceLive: '{{ __('monitoring.detail.checks.sources.live') }}',
        checkSourceArchived: '{{ __('monitoring.detail.checks.sources.archived') }}',
        checkResponseTimeUnavailable: '{{ __('monitoring.detail.checks.response_time_unavailable') }}',
    })">

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-container>
                <x-heading type="h2">{{ __('monitoring.detail.current_status') }}</x-heading>

                <div class="mt-1">
                    <div x-show="status"
                        :class="status === 'up' ? 'text-green-500' : (status === 'down' ? 'text-red-500' :
                            'text-yellow-500')">
                        <div>
                            <x-span x-text="status === 'up' ? '🟢' : (status === 'down' ? '🔴' : '🟡')"></x-span>
                            <x-span x-text="status ? status.toUpperCase() : ''" class="font-bold"></x-span>
                            <template x-if="statusCode !== null">
                                <x-span class="ml-2 text-sm font-medium text-gray-500 dark:text-gray-400"
                                    x-text="'HTTP ' + statusCode"></x-span>
                            </template>
                        </div>
                        <x-paragraph x-show="since" x-text="'{{ __('monitoring.index.table.since') }} ' + since"
                            class="text-gray-400">
                        </x-paragraph>
                        <template x-if="lastCheckedAt">
                            <x-paragraph x-text="'{{ __('monitoring.detail.last_check') }} ' + lastCheckedAtHuman"
                                class="text-gray-400"></x-paragraph>
                        </template>
                        <template x-if="intervalHuman">
                            <x-paragraph x-text="'{{ __('monitoring.detail.interval') }} ' + intervalHuman"
                                class="text-gray-400"></x-paragraph>
                        </template>
                    </div>
                    <template x-if="!status">
                        <div x-transition.opacity>
                            <x-loading-indicator>{{ __('monitoring.detail.no_data') }}</x-loading-indicator>
                        </div>
                    </template>
                </div>
            </x-container>

            @if ($regionalConsensus)
                <x-container>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <x-heading type="h2">{{ __('monitoring.detail.regional_consensus.heading') }}</x-heading>
                        <x-badge :type="match ($regionalConsensus['status']) {
                            \App\Enums\RegionalConsensusStatus::HEALTHY => 'success',
                            \App\Enums\RegionalConsensusStatus::LOCALIZED, \App\Enums\RegionalConsensusStatus::UNKNOWN => 'warning',
                            default => 'danger',
                        }">
                            {{ __('monitoring.detail.regional_consensus.statuses.' . $regionalConsensus['status']->value) }}
                        </x-badge>
                    </div>
                    <x-paragraph class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('monitoring.detail.regional_consensus.summary', [
                            'failed' => count($regionalConsensus['affected_locations']),
                            'reporting' => $regionalConsensus['reporting_locations'],
                            'total' => $regionalConsensus['total_locations'],
                            'required' => $regionalConsensus['required_failures'],
                        ]) }}
                    </x-paragraph>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($regionalConsensus['locations'] as $location)
                            <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                                <span class="font-medium text-gray-800 dark:text-gray-100">{{ $location['code'] }}</span>
                                <x-badge :type="match ($location['status']) {
                                    'up' => 'success',
                                    'down' => 'danger',
                                    default => 'warning',
                                }">
                                    {{ strtoupper($location['status']) }}
                                </x-badge>
                            </div>
                        @endforeach
                    </div>
                </x-container>
            @endif

            @if ($monitoring->type === MonitoringType::HTTP || $monitoring->type === MonitoringType::KEYWORD)
                <x-container>
                    <x-heading type="h2">{{ __('monitoring.detail.ssl.heading') }}</x-heading>

                    <template x-if="sslValid===true">
                        <div>
                            <x-paragraph
                                class="font-bold text-green-600 dark:text-green-600">{{ __('monitoring.detail.ssl.valid') }}</x-paragraph>
                            <x-paragraph class=""
                                x-text="'{{ __('monitoring.detail.ssl.expires_in') }}: ' + sslExpiration"></x-paragraph>
                            <template x-if="sslIssueDate">
                                <x-paragraph class=""
                                    x-text="'{{ __('monitoring.detail.ssl.issued_on') }}: ' + sslIssueDate"></x-paragraph>
                            </template>
                            <template x-if="sslIssuer">
                                <x-paragraph class=""
                                    x-text="'{{ __('monitoring.detail.ssl.issued_from') }}: ' + sslIssuer"></x-paragraph>
                            </template>

                        </div>
                    </template>

                    <template x-if="sslValid === false">
                        <div>
                            <x-paragraph
                                class="font-bold text-red-600 dark:text-red-600">{{ __('monitoring.detail.ssl.expired') }}</x-paragraph>
                        </div>
                    </template>

                    <template x-if="sslValid === null">
                        <div x-transition.opacity>
                            <x-loading-indicator>{{ __('monitoring.detail.no_data') }}</x-loading-indicator>
                        </div>
                    </template>
                </x-container>
            @endif

            @if ($monitoring->isHeartbeat())
                <x-container>
                    <x-heading type="h2">{{ __('monitoring.detail.heartbeat.heading') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-500">{{ __('monitoring.detail.heartbeat.ping_url') }}</x-paragraph>
                    <x-paragraph class="break-all font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->target }}</x-paragraph>
                    <x-paragraph class="mt-3 text-sm text-gray-500">
                        {{ trans_choice('monitoring.detail.heartbeat.cadence', $monitoring->heartbeat_interval_minutes ?? 0, ['minutes' => $monitoring->heartbeat_interval_minutes]) }}
                    </x-paragraph>
                    <x-paragraph class="text-sm text-gray-500">
                        {{ trans_choice('monitoring.detail.heartbeat.grace', $monitoring->heartbeat_grace_minutes ?? 0, ['minutes' => $monitoring->heartbeat_grace_minutes]) }}
                    </x-paragraph>
                    @if ($monitoring->heartbeat_last_ping_at)
                        <x-paragraph class="text-sm text-gray-500">
                            {{ __('monitoring.detail.heartbeat.last_ping') }} {{ $monitoring->heartbeat_last_ping_at->diffForHumans() }}
                        </x-paragraph>
                    @endif
                </x-container>
            @endif

            @if ($monitoring->type === MonitoringType::DOMAIN_EXPIRATION)
                <x-container>
                    <x-heading type="h2">{{ __('monitoring.detail.domain.heading') }}</x-heading>
                    @if ($monitoring->domainResult)
                        <x-paragraph
                            class="font-bold {{ $monitoring->domainResult->is_valid ? 'text-green-600 dark:text-green-600' : 'text-red-600 dark:text-red-600' }}">
                            {{ $monitoring->domainResult->is_valid ? __('monitoring.detail.domain.valid') : __('monitoring.detail.domain.invalid') }}
                        </x-paragraph>
                        @if ($monitoring->domainResult->expires_at)
                            <x-paragraph>
                                {{ __('monitoring.detail.domain.expires_at') }}:
                                {{ $monitoring->domainResult->expires_at->toFormattedDateString() }}
                            </x-paragraph>
                        @endif
                        @if ($monitoring->domainResult->registrar)
                            <x-paragraph>
                                {{ __('monitoring.detail.domain.registrar') }}:
                                {{ $monitoring->domainResult->registrar }}
                            </x-paragraph>
                        @endif
                    @else
                        <x-loading-indicator>{{ __('monitoring.detail.no_data') }}</x-loading-indicator>
                    @endif
                </x-container>
            @endif

            @if ($monitoring->type === MonitoringType::DNS_RECORD)
                <x-container>
                    <x-heading type="h2">{{ __('monitoring.detail.dns.heading') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-500">{{ __('monitoring.detail.dns.record_type') }}</x-paragraph>
                    <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->dns_record_type }}</x-paragraph>
                    <x-paragraph class="mt-3 text-sm text-gray-500">{{ __('monitoring.detail.dns.expected_values') }}</x-paragraph>
                    <ul class="mt-1 space-y-1">
                        @foreach (($monitoring->dns_expected_values ?? []) as $expectedValue)
                            <li class="break-all font-mono text-sm text-gray-800 dark:text-gray-100">{{ $expectedValue }}</li>
                        @endforeach
                    </ul>
                </x-container>
            @endif

            @if ($monitoring->type === MonitoringType::SERVER_HEALTH)
                <x-container>
                    <x-heading type="h2">{{ __('monitoring.detail.server_health.heading') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-500">{{ __('monitoring.detail.server_health.endpoint') }}</x-paragraph>
                    <x-paragraph class="break-all font-mono text-sm text-gray-800 dark:text-gray-100">{{ $monitoring->target }}</x-paragraph>
                    @if ($monitoring->server_health_last_reported_at)
                        <x-paragraph class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ __('monitoring.detail.server_health.last_report') }} {{ $monitoring->server_health_last_reported_at->diffForHumans() }}
                        </x-paragraph>
                    @endif
                    <div class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                        <div>
                            <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.cpu_threshold') }}</x-paragraph>
                            <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->server_health_cpu_threshold_percent }}%</x-paragraph>
                        </div>
                        <div>
                            <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.ram_threshold') }}</x-paragraph>
                            <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->server_health_ram_threshold_percent }}%</x-paragraph>
                        </div>
                        <div>
                            <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.storage_threshold') }}</x-paragraph>
                            <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->server_health_storage_threshold_percent }}%</x-paragraph>
                        </div>
                    </div>
                    <x-paragraph class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        <a href="{{ route('scribe') }}" target="_blank" rel="noopener"
                            class="text-purple-800 underline dark:text-purple-400">
                            {{ __('monitoring.detail.server_health.docs_link') }}
                        </a>
                    </x-paragraph>
                </x-container>
            @endif

            <x-container>
                <x-heading type="h2">{{ __('monitoring.detail.last_24_hours') }}</x-heading>
                <div id="heatmap">
                    <div class="flex gap-0.5">
                        <template x-if="loading">
                            <template x-for="n in 24" :key="n">
                                <div class="rounded-xs h-6 w-3 animate-pulse bg-gray-300 dark:bg-gray-400"></div>
                            </template>
                        </template>
                        <template x-if="!loading">
                            <template x-for="(dataPoint, index) in heatmap" :key="index">
                                <div class="rounded-xs h-6 w-3"
                                    :class="{
                                        'bg-green-500': dataPoint.uptime > dataPoint.downtime,
                                        'bg-red-500': dataPoint.uptime < dataPoint.downtime,
                                        'dark:bg-gray-400 bg-gray-300': dataPoint.uptime === dataPoint.downtime
                                    }">
                                </div>
                            </template>
                        </template>
                    </div>
                </div>

                <div class="mt-2 flex items-center gap-4">
                    <div class="flex items-center gap-1">
                        <div class="rounded-xs h-3 w-3 bg-green-500"></div>
                        <x-span>{{ __('monitoring.detail.availability.up') }}</x-span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="rounded-xs h-3 w-3 bg-red-500"></div>
                        <x-span>{{ __('monitoring.detail.availability.down') }}</x-span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="rounded-xs h-3 w-3 bg-gray-300"></div>
                        <x-span>{{ __('monitoring.detail.availability.unknown') }}</x-span>
                    </div>
                </div>
            </x-container>

            @foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'] as $key => $label)
                <x-container id="uptime-card-{{ $key }}">
                    <x-heading type="h2" class="capitalize">{{ $label }}</x-heading>
                    <x-paragraph class="text-2xl font-bold text-purple-600"
                        x-text="uptimeStats['{{ $key }}']?.has_data && uptimeStats['{{ $key }}']?.uptime?.percentage !== null
                            ? uptimeStats['{{ $key }}'].uptime.percentage.toFixed(2) + '%'
                            : '—'">
                        —%
                    </x-paragraph>
                    <x-paragraph class="text-gray-400"
                        x-text="uptimeStats['{{ $key }}'] && uptimeStats['{{ $key }}'].downtime
                                ? uptimeStats['{{ $key }}'].downtime.incidents_count + ' {{ __('monitoring.detail.incidents.heading') }}, ' + uptimeStats['{{ $key }}'].downtime.human_readable + ' {{ __('monitoring.detail.downtime') }}'
                                : '— {{ __('monitoring.detail.incidents.heading') }}, {{ __('monitoring.detail.downtime') }} —'">
                        — {{ __('monitoring.detail.incidents.heading') }}, {{ __('monitoring.detail.downtime') }}
                        —
                    </x-paragraph>
                </x-container>
            @endforeach

        </div>

        <div class="my-4" id="uptime-calendar-{{ $monitoring->id }}">
            <x-heading type="h2" class="mb-2">{{ __('monitoring.detail.calendar.heading') }}</x-heading>

            <template x-if="uptimeCalendarLoading">
                <x-loading-indicator>{{ __('monitoring.detail.calendar.loading') }}</x-loading-indicator>
            </template>
            <template x-if="!uptimeCalendarLoading && uptimeCalendarData">
                <div x-data="{ data: uptimeCalendarData }">
                    @include('components.monitoring-calendar')
                </div>
            </template>
        </div>

        @if (! in_array($monitoring->type, [MonitoringType::PING, MonitoringType::HEARTBEAT, MonitoringType::SERVER_HEALTH, MonitoringType::DOMAIN_EXPIRATION], true))
            <div class="mb-2 flex items-center justify-between">
                <x-heading type="h2">{{ __('monitoring.detail.response_time.heading') }}</x-heading>

                <div>
                    <label for="response-time-range" class="hidden">{{ __('monitoring.filter.heading') }}</label>

                    <select id="response-time-range" x-model="responseTimeRange"
                        @change="loadPerformanceChart(responseTimeRange)"
                        class="rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="1">{{ __('monitoring.filter.options.today') }}</option>
                        <option value="7">{{ __('monitoring.filter.options.last_week') }}</option>
                        <option value="30">{{ __('monitoring.filter.options.last_month') }}</option>
                        <option value="90">{{ __('monitoring.filter.options.last_quarter') }}</option>
                        <option value="365">{{ __('monitoring.filter.options.last_year') }}</option>
                    </select>
                </div>
            </div>

            <x-container space="true">
                <div :class="{ 'hidden': chartLoading }" x-transition.opacity>
                    <canvas id="performance-chart" class="min-h-[40vh]"></canvas>
                </div>
                <div x-show="chartLoading" x-transition.opacity>
                    <x-loading-indicator>{{ __('monitoring.detail.no_data') }}</x-loading-indicator>
                </div>
            </x-container>

            <template x-if="responseStats[responseTimeRange + 'd']">
                <div class="mb-4 grid grid-cols-1 gap-4 text-center md:grid-cols-3">
                    <x-container>
                        <x-paragraph
                            class="text-gray-500">{{ __('monitoring.detail.response_time.min') }}</x-paragraph>
                        <x-paragraph class="text-xl font-semibold text-gray-800"
                            x-text="responseStats[responseTimeRange + 'd']?.avg !== undefined ? Math.round(responseStats[responseTimeRange + 'd'].avg) + ' ms' : '—'">
                            —
                        </x-paragraph>
                    </x-container>
                    <x-container>
                        <x-paragraph
                            class="text-gray-500">{{ __('monitoring.detail.response_time.avg') }}</x-paragraph>
                        <x-paragraph class="text-xl font-semibold text-gray-800"
                            x-text="responseStats[responseTimeRange + 'd']?.avg !== undefined ? Math.round(responseStats[responseTimeRange + 'd'].avg) + ' ms' : '—'">
                            —
                        </x-paragraph>
                    </x-container>
                    <x-container>
                        <x-paragraph
                            class="text-gray-500">{{ __('monitoring.detail.response_time.max') }}</x-paragraph>
                        <x-paragraph class="text-xl font-semibold text-gray-800"
                            x-text="responseStats[responseTimeRange + 'd']?.max !== undefined ? Math.round(responseStats[responseTimeRange + 'd'].max) + ' ms' : '—'">
                            —
                        </x-paragraph>
                    </x-container>
                </div>
            </template>
        @endif

        <div id="incidents" class="mt-4">
            <div class="mb-2 flex items-center justify-between gap-4">
                <x-heading type="h2"
                    class="text-lg font-semibold text-gray-800">{{ __('monitoring.detail.incidents.heading') }}
                </x-heading>

                <div>
                    <label for="incidents-range" class="hidden">{{ __('monitoring.filter.heading') }}</label>

                    <select id="incidents-range" x-model="incidentsRange"
                        @change="loadIncidents(incidentsRange)"
                        class="rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="1">{{ __('monitoring.filter.options.today') }}</option>
                        <option value="7">{{ __('monitoring.filter.options.last_week') }}</option>
                        <option value="30">{{ __('monitoring.filter.options.last_month') }}</option>
                        <option value="90">{{ __('monitoring.filter.options.last_quarter') }}</option>
                        <option value="365">{{ __('monitoring.filter.options.last_year') }}</option>
                    </select>
                </div>
            </div>

            <template x-if="incidentsLoading">
                <div x-transition.opacity>
                    <x-loading-indicator>{{ __('monitoring.detail.incidents.loading') }}</x-loading-indicator>
                </div>
            </template>

            <template x-if="!incidentsLoading && incidents.length === 0">
                <x-paragraph class="text-gray-500">{{ __('monitoring.detail.incidents.no_incidents') }}</x-paragraph>
            </template>

            <template x-if="!incidentsLoading && incidents.length > 0">
                <template x-for="incident in incidents" :key="incident.down_at">
                    <x-container space="true">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <x-span
                                    class="block text-gray-500">{{ __('monitoring.detail.incidents.incident.down_at') }}</x-span>
                                <x-span class="font-medium text-red-600 dark:text-red-600"
                                    x-text="incident.down_at"></x-span>
                            </div>
                            <div x-show="incident.up_at">
                                <x-span
                                    class="block text-gray-500">{{ __('monitoring.detail.incidents.incident.up_at') }}</x-span>
                                <x-span class="font-medium text-green-600 dark:text-green-600"
                                    x-text="incident.up_at"></x-span>
                            </div>
                            <div x-show="incident.duration">
                                <x-span
                                    class="block text-gray-500">{{ __('monitoring.detail.incidents.incident.duration') }}</x-span>
                                <x-span class="font-medium text-gray-800 dark:text-gray-400"
                                    x-text="incident.duration"></x-span>
                            </div>
                        </div>
                    </x-container>
                </template>
            </template>
        </div>

        <div id="recent-checks" class="mt-8">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <x-heading type="h2" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('monitoring.detail.checks.heading') }}
                    </x-heading>
                    <x-paragraph class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        x-show="!recentChecksLoading && recentChecks.length > 0">
                        <span x-text="recentChecks.length"></span>
                        <span>{{ __('monitoring.detail.checks.summary_suffix') }}</span>
                    </x-paragraph>
                </div>
            </div>

            <template x-if="recentChecksLoading">
                <div x-transition.opacity>
                    <x-loading-indicator>{{ __('monitoring.detail.checks.loading') }}</x-loading-indicator>
                </div>
            </template>

            <template x-if="!recentChecksLoading && recentChecks.length === 0">
                <x-paragraph class="text-gray-500">{{ __('monitoring.detail.checks.no_checks') }}</x-paragraph>
            </template>

            <template x-if="!recentChecksLoading && recentChecks.length > 0">
                <div>
                    <div
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="hidden grid-cols-[minmax(13rem,1.25fr)_minmax(0,3fr)_auto] gap-4 border-b border-gray-100 bg-gray-50/80 px-5 py-3 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-900/35 dark:text-gray-400 lg:grid">
                            <span>{{ __('monitoring.detail.checks.labels.checked_at') }}</span>
                            <span>{{ __('monitoring.detail.checks.labels.result') }}</span>
                            <span class="text-right">{{ __('monitoring.detail.checks.labels.status') }}</span>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-700/80">
                            <template x-for="(check, index) in recentChecks" :key="check.id">
                                <div
                                    class="grid gap-4 px-4 py-4 transition duration-150 hover:bg-gray-50/80 dark:hover:bg-gray-900/30 sm:px-5 lg:grid-cols-[minmax(13rem,1.25fr)_minmax(0,3fr)_auto] lg:items-center">
                                    <div class="flex min-w-0 gap-3">
                                        <div class="relative flex w-5 shrink-0 justify-center">
                                            <span
                                                class="mt-1 flex size-5 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                <svg class="size-3.5" viewBox="0 0 16 16" aria-hidden="true">
                                                    <path fill="currentColor"
                                                        d="M6.35 11.15 2.9 7.7l1.05-1.05 2.4 2.4 5.7-5.7L13.1 4.4z" />
                                                </svg>
                                            </span>
                                            <span
                                                class="absolute top-7 bottom-[-1rem] w-px bg-emerald-100 dark:bg-emerald-900"
                                                x-show="index < recentChecks.length - 1"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-950 dark:text-gray-100"
                                                x-text="check.checkedAt"></p>
                                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                                x-text="check.checkedAtHuman"></p>
                                        </div>
                                    </div>

                                    <div
                                        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(6.5rem,0.8fr)_minmax(10rem,1.2fr)_minmax(5rem,0.7fr)_minmax(5rem,0.7fr)]"
                                        x-bind:class="hasServerHealthMetrics(check.serverHealthMetrics) ? 'xl:grid-cols-[minmax(6.5rem,0.8fr)_minmax(10rem,1.2fr)_minmax(9rem,1.2fr)_minmax(5rem,0.7fr)_minmax(5rem,0.7fr)]' : ''">
                                        <div>
                                            <span
                                                class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                {{ __('monitoring.detail.checks.labels.status_code') }}
                                            </span>
                                            <span class="mt-1 block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                                x-text="check.httpStatusCode ?? '{{ __('monitoring.detail.checks.status_code_unavailable') }}'"></span>
                                        </div>

                                        <div>
                                            <div class="flex items-baseline justify-between gap-3">
                                                <span
                                                    class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                    {{ __('monitoring.detail.checks.labels.response_time') }}
                                                </span>
                                                <span class="text-sm font-semibold text-sky-700 dark:text-sky-300"
                                                    x-text="formatResponseTime(check.responseTime)"></span>
                                            </div>
                                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                                <div class="h-full rounded-full bg-sky-500 dark:bg-sky-400"
                                                    x-bind:style="`width: ${responseTimeBarWidth(check.responseTime)}%`"></div>
                                            </div>
                                        </div>

                                        <div x-show="hasServerHealthMetrics(check.serverHealthMetrics)">
                                            <span
                                                class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                {{ __('monitoring.detail.checks.labels.server_health') }}
                                            </span>
                                            <span class="mt-1 block truncate text-sm text-gray-900 dark:text-gray-100"
                                                x-text="formatServerHealthMetrics(check.serverHealthMetrics)"></span>
                                        </div>

                                        <div>
                                            <span
                                                class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                {{ __('monitoring.detail.checks.labels.source') }}
                                            </span>
                                            <span class="mt-1 block text-sm text-gray-900 dark:text-gray-100"
                                                x-text="resolveCheckSourceLabel(check.source)"></span>
                                        </div>

                                        <div>
                                            <span
                                                class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                {{ __('monitoring.detail.checks.labels.raw_status') }}
                                            </span>
                                            <span class="mt-1 block text-sm font-semibold uppercase text-gray-900 dark:text-gray-100"
                                                x-text="check.status"></span>
                                        </div>
                                    </div>

                                    <div class="flex justify-start lg:justify-end">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase"
                                            x-bind:class="resolveCheckStatusClass(check.statusIdentifier)"
                                            x-text="resolveCheckStatusLabel(check.statusIdentifier)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-5 text-center" x-show="recentChecksHasMore">
                        <button type="button" @click="loadMoreChecks()"
                            class="inline-flex items-center rounded-md border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold uppercase text-emerald-700 shadow-sm transition duration-150 hover:border-emerald-300 hover:bg-emerald-50 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:border-emerald-800 dark:bg-gray-800 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                            x-bind:disabled="recentChecksLoadingMore"
                            x-bind:class="{ 'opacity-60 cursor-not-allowed': recentChecksLoadingMore }">
                            <span
                                x-text="recentChecksLoadingMore ? '{{ __('monitoring.detail.checks.loading_more') }}' : '{{ __('monitoring.detail.checks.load_more') }}'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-main>
</x-app-layout>
