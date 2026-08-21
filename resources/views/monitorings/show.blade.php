@php
    use App\Enums\MonitoringStatus;
    use App\Enums\MonitoringType;

    $latestStatus = $monitoring->latestResponseResult?->status;
    $statusTone = match ($latestStatus?->value) {
        'up' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-300', 'label' => __('monitoring.detail.availability.up')],
        'down' => ['dot' => 'bg-red-500', 'text' => 'text-red-700 dark:text-red-300', 'label' => __('monitoring.detail.availability.down')],
        default => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-300', 'label' => __('monitoring.detail.availability.unknown')],
    };
    $notificationChannels = $monitoring->notification_channels ?? [];
    $statusPages = $monitoring->statusPageComponents
        ->map(fn ($component) => $component->statusPage)
        ->filter()
        ->unique('id')
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div data-monitoring-detail-header class="space-y-4">
            <a
                href="{{ route('monitorings.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 transition hover:text-purple-700 dark:text-gray-400 dark:hover:text-purple-300"
            >
                <span aria-hidden="true">←</span>
                {{ __('monitoring.detail.back_to_overview') }}
            </a>

            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold tracking-[0.14em] uppercase">
                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                            {{ __('monitoring.types.' . $monitoring->type->value) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 {{ $statusTone['text'] }} dark:bg-gray-800">
                            <span class="h-2 w-2 rounded-full {{ $statusTone['dot'] }}"></span>
                            {{ $statusTone['label'] }}
                        </span>
                        @if ($monitoring->isPaused())
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">{{ __('monitoring.index.table.paused') }}</span>
                        @elseif ($monitoring->isUnderMaintenance())
                            <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">{{ __('monitoring.index.table.maintenance') }}</span>
                        @endif
                    </div>
                    <x-heading type="h1" class="mt-3 break-words">{{ $monitoring->name }}</x-heading>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                        <span class="max-w-full break-all">{{ $monitoring->target }}</span>
                        @if ($monitoring->public_label_enabled)
                            <a
                                href="{{ route('public-label', $monitoring) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-purple-700 transition hover:bg-purple-50 hover:text-purple-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden dark:text-purple-300 dark:hover:bg-purple-950/40 dark:hover:text-purple-200"
                                title="{{ __('monitoring.detail.open_public_label') }}"
                                aria-label="{{ __('monitoring.detail.open_public_label') }}"
                            >
                                <x-icon name="external-link" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>

                <div
                    x-data="formModalLoader()"
                    data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}"
                    class="w-full lg:ml-auto lg:w-auto"
                >
                    @if ($canManageMonitoring)
                        <div class="flex w-full justify-end lg:w-auto">
                            <div
                                data-monitoring-actions
                                class="relative"
                                x-data="{ open: false }"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                            >
                                <x-secondary-button
                                    type="button"
                                    data-monitoring-actions-trigger
                                    @click="open = ! open"
                                    :icon-only="true"
                                    :title="__('monitoring.actions.heading')"
                                    :aria-label="__('monitoring.actions.heading')"
                                    x-bind:aria-expanded="open.toString()"
                                    aria-controls="monitoring-actions-menu"
                                >
                                    <x-icon name="ellipsis" class="h-4 w-4" />
                                </x-secondary-button>

                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    id="monitoring-actions-menu"
                                    class="absolute end-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                    style="display: none"
                                >
                                    <a
                                        href="{{ route('monitorings.edit', ['monitoring' => $monitoring->id]) }}"
                                        data-form-modal-trigger
                                        data-form-modal-name="monitoring-form-modal"
                                        class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-purple-500 focus:outline-hidden dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        <x-icon name="pencil" class="h-4 w-4" />
                                        <span>{{ __('monitoring.actions.edit') }}</span>
                                    </a>
                                    @if ($monitoring->isServerHealth())
                                        <form
                                            method="POST"
                                            action="{{ route('monitorings.server-health-token.rotate', $monitoring) }}"
                                            data-confirm-message="{{ __('monitoring.actions.server_health_token.rotate_confirmation') }}"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-start text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-purple-500 focus:outline-hidden dark:text-gray-200 dark:hover:bg-gray-700"
                                            >
                                                <x-icon name="refresh" class="h-4 w-4" />
                                                <span>{{ __('monitoring.actions.server_health_token.rotate') }}</span>
                                            </button>
                                        </form>
                                    @endif
                                    <form
                                        method="POST"
                                        action="{{ route('monitorings.destroyResults', $monitoring) }}"
                                        data-confirm-message="{{ __('monitoring.actions.reset.confirmation') }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-start text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-purple-500 focus:outline-hidden dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            <x-icon name="refresh" class="h-4 w-4" />
                                            <span>{{ __('monitoring.actions.reset.heading') }}</span>
                                        </button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route('monitorings.destroy', $monitoring) }}"
                                        data-confirm-message="{{ __('monitoring.actions.delete.confirmation') }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-start text-sm font-semibold text-red-600 hover:bg-red-50 focus:ring-2 focus:ring-red-500 focus:outline-hidden dark:text-red-400 dark:hover:bg-red-950/30"
                                        >
                                            <x-icon name="trash" class="h-4 w-4" />
                                            <span>{{ __('monitoring.actions.delete.heading') }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    <x-form-modal
                        name="monitoring-form-modal"
                        title="{{ __('monitoring.title') }}"
                        description="{{ __('monitoring.form.sections.basic') }}"
                        max-width="6xl"
                    >
                        <div class="p-6" x-ref="content">
                            <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                            <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                            <div x-html="content"></div>
                        </div>
                    </x-form-modal>
                </div>
            </div>
    </x-slot>

    <x-main
        x-init="loadStatus();
    loadHeatmap();
    loadUptime();
    loadPerformanceChart(responseTimeRange);
    {{ $monitoring->type === MonitoringType::SERVER_HEALTH ? 'loadServerHealthTelemetry(serverHealthTelemetryRange);' : '' }}
    loadIncidents(incidentsRange);
    loadChecks();
    initializeDeferredLoads();"
        x-data="monitoringDetail('{{ $monitoring->id }}', {
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
        serverHealthCpuUsage: '{{ __('monitoring.detail.server_health.cpu_usage') }}',
        serverHealthRamUsage: '{{ __('monitoring.detail.server_health.ram_usage') }}',
        serverHealthStorageUsage: '{{ __('monitoring.detail.server_health.storage_usage') }}',
        serverHealthNormalizedLoad: '{{ __('monitoring.detail.server_health.normalized_load') }}',
        serverHealthThreshold: '{{ __('monitoring.detail.server_health.threshold') }}',
        serverHealthPercentAxis: '{{ __('monitoring.detail.server_health.percent_axis') }}',
        serverHealthLoadAxis: '{{ __('monitoring.detail.server_health.load_axis') }}',
    })"
    >
        @if ($initialResultsWaitMinutes !== null)
            <div
                data-initial-results-notice
                role="status"
                class="mb-6 flex gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path stroke-linecap="round" d="M12 10v6m0-9h.01" />
                </svg>
                <p>{{ __('monitoring.detail.initial_results_notice', ['minutes' => $initialResultsWaitMinutes]) }}</p>
            </div>
        @endif

        <section
            data-monitoring-summary
            class="mb-6 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
            aria-label="{{ __('monitoring.detail.summary_heading') }}"
        >
            <x-container class="!rounded-2xl !border-gray-200 !shadow-sm dark:!border-gray-700">
                <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                    {{ __('monitoring.detail.summary.current_status') }}
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <span
                        class="h-3 w-3 rounded-full"
                        :class="status === 'up' ? 'bg-emerald-500' : status === 'down' ? 'bg-red-500' : 'bg-amber-500'"
                    ></span>
                    <span
                        class="text-xl font-black text-gray-950 dark:text-white"
                        x-text="status ? status.toUpperCase() : '—'"
                    ></span>
                    <span
                        class="text-sm text-gray-500 dark:text-gray-400"
                        x-text="statusCode ? 'HTTP ' + statusCode : ''"
                    ></span>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="since">
                    {{ __('monitoring.index.table.since') }}
                    <time x-bind:datetime="sinceDate" x-bind:title="since" x-text="since"></time>
                </p>
            </x-container>

            <x-container class="!rounded-2xl !border-gray-200 !shadow-sm dark:!border-gray-700">
                <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                    {{ __('monitoring.detail.summary.last_check') }}
                </p>
                <p class="mt-3 text-xl font-black text-gray-950 dark:text-white">
                    <time
                        x-show="lastCheckedAtHuman"
                        x-bind:datetime="lastCheckedAt"
                        x-bind:title="lastCheckedAtHuman"
                        x-text="lastCheckedAtHuman"
                    ></time>
                    <span x-show="! lastCheckedAtHuman">—</span>
                </p>
                <p
                    class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                    x-text="intervalHuman ? '{{ __('monitoring.detail.interval') }} ' + intervalHuman : '{{ __('monitoring.detail.summary.waiting_for_check') }}'"
                ></p>
            </x-container>

            <x-container class="!rounded-2xl !border-gray-200 !shadow-sm dark:!border-gray-700">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                        {{ __('monitoring.detail.summary.last_24_hours') }}
                    </p>
                    <span
                        class="text-sm font-black text-purple-700 dark:text-purple-300"
                        x-text="
                            uptimeStats['1']?.uptime?.percentage !== null &&
                            uptimeStats['1']?.uptime?.percentage !== undefined
                                ? uptimeStats['1'].uptime.percentage.toFixed(2) + '%'
                                : '—'
                        "
                    ></span>
                </div>
                <div class="mt-4 grid grid-cols-12 gap-0.5 sm:flex sm:flex-nowrap" aria-hidden="true">
                    <template x-for="(dataPoint, index) in heatmap" :key="'summary-' + index">
                        <span
                            class="min-w-0 flex-1 rounded-full"
                            :class="dataPoint.uptime > dataPoint.downtime
                                ? 'bg-emerald-400'
                                : dataPoint.uptime < dataPoint.downtime
                                  ? 'bg-red-400'
                                  : 'bg-gray-300 dark:bg-gray-600'"
                            :style="`height: ${dataPoint.uptime === dataPoint.downtime ? 35 : 100}%`"
                        ></span>
                    </template>
                </div>
                <p
                    class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                    x-text="uptimeStats['1']?.downtime ? uptimeStats['1'].downtime.incidents_count + ' {{ __('monitoring.detail.incidents.heading') }}, ' + uptimeStats['1'].downtime.human_readable + ' {{ __('monitoring.detail.downtime') }}' : '{{ __('monitoring.detail.summary.no_incidents') }}'"
                ></p>
            </x-container>
        </section>

        <div data-monitoring-detail-layout class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-6">
                <div
                    data-monitoring-primary-cards
                    class="mb-4 grid grid-cols-1 items-stretch gap-4 md:grid-cols-2 2xl:grid-cols-3"
                >
                    @if ($regionalConsensus)
                        <x-container>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-heading type="h2">{{ __('monitoring.detail.regional_consensus.heading') }}</x-heading>
                                <x-badge
                                    :type="match ($regionalConsensus['status']) {
                            \App\Enums\RegionalConsensusStatus::HEALTHY => 'success',
                            \App\Enums\RegionalConsensusStatus::LOCALIZED, \App\Enums\RegionalConsensusStatus::UNKNOWN => 'warning',
                            default => 'danger',
                        }"
                                >
                                    {{ __('monitoring.detail.regional_consensus.statuses.' . $regionalConsensus['status']->value) }}
                                </x-badge>
                            </div>
                            <x-paragraph class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{
                                    __('monitoring.detail.regional_consensus.summary', [
                                        'failed' => count($regionalConsensus['affected_locations']),
                                        'reporting' => $regionalConsensus['reporting_locations'],
                                        'total' => $regionalConsensus['total_locations'],
                                        'required' => $regionalConsensus['required_failures'],
                                    ])
                                }}
                            </x-paragraph>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($regionalConsensus['locations'] as $location)
                                    <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $location['code'] }}</span>
                                        <x-badge
                                            :type="match ($location['status']) {
                                    'up' => 'success',
                                    'down' => 'danger',
                                    default => 'warning',
                                }"
                                        >
                                            {{ strtoupper($location['status']) }}
                                        </x-badge>
                                    </div>
                                @endforeach
                            </div>
                        </x-container>
                    @endif

                    @if ($monitoring->isHeartbeat())
                        <x-container>
                            <x-heading type="h2">{{ __('monitoring.detail.heartbeat.heading') }}</x-heading>
                            <x-paragraph class="mt-2 text-sm text-gray-500">{{ __('monitoring.detail.heartbeat.ping_url') }}</x-paragraph>
                            <x-paragraph class="font-medium break-all text-gray-800 dark:text-gray-100">{{ $monitoring->target }}</x-paragraph>
                            <x-paragraph class="mt-3 text-sm text-gray-500">
                                {{ trans_choice('monitoring.detail.heartbeat.cadence', $monitoring->heartbeat_interval_minutes ?? 0, ['minutes' => $monitoring->heartbeat_interval_minutes]) }}
                            </x-paragraph>
                            <x-paragraph class="text-sm text-gray-500">
                                {{ trans_choice('monitoring.detail.heartbeat.grace', $monitoring->heartbeat_grace_minutes ?? 0, ['minutes' => $monitoring->heartbeat_grace_minutes]) }}
                            </x-paragraph>
                            @if ($monitoring->heartbeat_last_ping_at)
                                <x-paragraph class="text-sm text-gray-500">
                                    {{ __('monitoring.detail.heartbeat.last_ping') }}
                                    <x-date-time :value="$monitoring->heartbeat_last_ping_at" />
                                </x-paragraph>
                            @endif
                        </x-container>
                    @endif

                    @if ($monitoring->type === MonitoringType::DOMAIN_EXPIRATION)
                        <x-container>
                            <x-heading type="h2">{{ __('monitoring.detail.domain.heading') }}</x-heading>
                            @if ($monitoring->domainResult)
                                <x-paragraph class="font-bold {{ $monitoring->domainResult->is_valid ? 'text-green-600 dark:text-green-600' : 'text-red-600 dark:text-red-600' }}">
                                    {{ $monitoring->domainResult->is_valid ? __('monitoring.detail.domain.valid') : __('monitoring.detail.domain.invalid') }}
                                </x-paragraph>
                                @if ($monitoring->domainResult->expires_at)
                                    <x-paragraph>
                                        {{ __('monitoring.detail.domain.expires_at') }}:
                                        <x-date-time :value="$monitoring->domainResult->expires_at" format="date" />
                                    </x-paragraph>
                                @endif
                                @if ($monitoring->domainResult->registrar)
                                    <x-paragraph>
                                        {{ __('monitoring.detail.domain.registrar') }}: {{ $monitoring->domainResult->registrar }}
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
                                    <li class="font-mono text-sm break-all text-gray-800 dark:text-gray-100">
                                        {{ $expectedValue }}
                                    </li>
                                @endforeach
                            </ul>
                        </x-container>
                    @endif

                    @if ($monitoring->type === MonitoringType::SERVER_HEALTH)
                        <x-container>
                            <x-heading type="h2">{{ __('monitoring.detail.server_health.heading') }}</x-heading>
                            <x-paragraph class="mt-2 text-sm text-gray-500">{{ __('monitoring.detail.server_health.endpoint') }}</x-paragraph>
                            <x-paragraph class="font-mono text-sm break-all text-gray-800 dark:text-gray-100">{{ $monitoring->target }}</x-paragraph>
                            @if ($monitoring->server_health_last_reported_at)
                                <x-paragraph class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('monitoring.detail.server_health.last_report') }}
                                    <x-date-time :value="$monitoring->server_health_last_reported_at" />
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
                                    <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.load_threshold') }}</x-paragraph>
                                    <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->server_health_load_threshold_per_cpu ?? '—' }}</x-paragraph>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                                <div>
                                    <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.service_response_time_threshold') }}</x-paragraph>
                                    <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ $monitoring->server_health_service_response_time_threshold_ms ? $monitoring->server_health_service_response_time_threshold_ms . ' ms' : '—' }}</x-paragraph>
                                </div>
                                <div>
                                    <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.report_interval') }}</x-paragraph>
                                    <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ trans_choice('monitoring.detail.server_health.minutes', $monitoring->server_health_report_interval_minutes ?? 1) }}</x-paragraph>
                                </div>
                                <div>
                                    <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.grace') }}</x-paragraph>
                                    <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">{{ trans_choice('monitoring.detail.server_health.minutes', $monitoring->server_health_grace_minutes ?? 5) }}</x-paragraph>
                                </div>
                            </div>
                            @php($serverHealthMetrics = $monitoring->latestResponseResult?->server_health_metrics ?? [])
                            @if ($serverHealthMetrics !== [])
                                <x-heading
                                    type="h3"
                                    class="mt-6 text-base"
                                >{{ __('monitoring.detail.server_health.current_metrics') }}</x-heading>
                                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-4">
                                    @foreach (['cpu_usage_percent' => 'cpu', 'ram_usage_percent' => 'ram', 'load_average_1m' => 'load', 'uptime_seconds' => 'uptime'] as $metric => $label)
                                        @if (array_key_exists($metric, $serverHealthMetrics))
                                            <div>
                                                <x-paragraph class="text-gray-500">{{ __('monitoring.detail.server_health.' . $label) }}</x-paragraph>
                                                <x-paragraph class="font-medium text-gray-800 dark:text-gray-100">
                                                    {{ in_array($metric, ['cpu_usage_percent', 'ram_usage_percent'], true) ? $serverHealthMetrics[$metric] . '%' : $serverHealthMetrics[$metric] }}
                                                </x-paragraph>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                @if (is_array($serverHealthMetrics['service_checks'] ?? null) && $serverHealthMetrics['service_checks'] !== [])
                                    <x-paragraph class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ __('monitoring.detail.server_health.service_checks', ['count' => count($serverHealthMetrics['service_checks'])]) }}
                                    </x-paragraph>
                                @endif
                            @endif
                            <x-paragraph class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                <a
                                    href="{{ route('scribe') }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-purple-800 underline dark:text-purple-400"
                                >
                                    {{ __('monitoring.detail.server_health.docs_link') }}
                                </a>
                            </x-paragraph>
                        </x-container>

                        <div data-server-health-telemetry class="mb-4">
                            <div class="mb-2 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <x-heading type="h2">{{ __('monitoring.detail.server_health.history') }}</x-heading>
                                    <x-paragraph class="text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.server_health.history_help') }}</x-paragraph>
                                </div>
                                <label
                                    for="server-health-telemetry-range"
                                    class="sr-only"
                                >{{ __('monitoring.filter.heading') }}</label>
                                <select
                                    id="server-health-telemetry-range"
                                    x-model="serverHealthTelemetryRange"
                                    @change="loadServerHealthTelemetry(serverHealthTelemetryRange)"
                                    class="focus:ring-opacity-50 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option value="1">{{ __('monitoring.filter.options.today') }}</option>
                                    <option value="7">{{ __('monitoring.filter.options.last_week') }}</option>
                                    <option value="30">{{ __('monitoring.filter.options.last_month') }}</option>
                                </select>
                            </div>
                            <x-container>
                                <div :class="{ hidden: serverHealthTelemetryLoading }" x-transition.opacity>
                                    <canvas id="server-health-telemetry-chart" class="min-h-[24rem]"></canvas>
                                </div>
                                <div x-show="serverHealthTelemetryLoading" x-transition.opacity>
                                    <x-loading-indicator>{{ __('monitoring.detail.server_health.history_loading') }}</x-loading-indicator>
                                </div>
                            </x-container>
                        </div>
                    @endif

                    @foreach (['7' => 'monitoring.detail.uptime_periods.last_7', '30' => 'monitoring.detail.uptime_periods.last_30', '90' => 'monitoring.detail.uptime_periods.last_90'] as $key => $label)
                        <x-container id="uptime-card-{{ $key }}">
                            <x-heading type="h2" class="capitalize">{{ __($label) }}</x-heading>
                            <x-paragraph
                                class="text-2xl font-bold text-purple-600"
                                x-text="uptimeStats['{{ $key }}']?.has_data && uptimeStats['{{ $key }}']?.uptime?.percentage !== null
                            ? uptimeStats['{{ $key }}'].uptime.percentage.toFixed(2) + '%'
                            : '—'"
                            >
                                —%
                            </x-paragraph>
                            <x-paragraph
                                class="text-gray-400"
                                x-text="uptimeStats['{{ $key }}'] && uptimeStats['{{ $key }}'].downtime
                                ? uptimeStats['{{ $key }}'].downtime.incidents_count + ' {{ __('monitoring.detail.incidents.heading') }}, ' + uptimeStats['{{ $key }}'].downtime.human_readable + ' {{ __('monitoring.detail.downtime') }}'
                                : '— {{ __('monitoring.detail.incidents.heading') }}, {{ __('monitoring.detail.downtime') }} —'"
                            >
                                — {{ __('monitoring.detail.incidents.heading') }}, {{ __('monitoring.detail.downtime') }} —
                            </x-paragraph>
                        </x-container>
                    @endforeach
                </div>

                <div class="my-4" id="uptime-calendar-{{ $monitoring->id }}">
                    <x-heading type="h2" class="mb-2">{{ __('monitoring.detail.calendar.heading') }}</x-heading>

                    <template x-if="uptimeCalendarLoading">
                        <x-loading-indicator>{{ __('monitoring.detail.calendar.loading') }}</x-loading-indicator>
                    </template>
                    <template x-if="! uptimeCalendarLoading && uptimeCalendarData">
                        <div x-data="{ data: uptimeCalendarData }">
                            @include('components.monitoring-calendar')
                        </div>
                    </template>
                </div>

                @if (! in_array($monitoring->type, [MonitoringType::PING, MonitoringType::HEARTBEAT, MonitoringType::SERVER_HEALTH, MonitoringType::DOMAIN_EXPIRATION], true))
                    <div class="mb-2 flex items-center justify-between">
                        <x-heading type="h2">{{ __('monitoring.detail.response_time.heading') }}</x-heading>

                        <div>
                            <label
                                for="response-time-range"
                                class="hidden"
                            >{{ __('monitoring.filter.heading') }}</label>

                            <select
                                id="response-time-range"
                                x-model="responseTimeRange"
                                @change="loadPerformanceChart(responseTimeRange)"
                                class="focus:ring-opacity-50 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            >
                                <option value="1">{{ __('monitoring.filter.options.today') }}</option>
                                <option value="7">{{ __('monitoring.filter.options.last_week') }}</option>
                                <option value="30">{{ __('monitoring.filter.options.last_month') }}</option>
                                <option value="90">{{ __('monitoring.filter.options.last_quarter') }}</option>
                                <option value="365">{{ __('monitoring.filter.options.last_year') }}</option>
                            </select>
                        </div>
                    </div>

                    @if ($monitoring->response_time_threshold_ms !== null)
                        <x-container class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <x-paragraph class="font-semibold text-gray-900 dark:text-gray-100">{{ __('monitoring.detail.performance.heading') }}</x-paragraph>
                                <x-paragraph class="text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.performance.threshold', ['threshold' => $monitoring->response_time_threshold_ms]) }}</x-paragraph>
                            </div>
                            @if ($monitoring->performanceState?->status?->value === 'degraded')
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ __('monitoring.detail.performance.degraded') }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ __('monitoring.detail.performance.normal') }}</span>
                            @endif
                        </x-container>
                    @endif

                    <x-container space="true">
                        <div :class="{ hidden: chartLoading }" x-transition.opacity>
                            <canvas id="performance-chart" class="min-h-[40vh]"></canvas>
                        </div>
                        <div x-show="chartLoading" x-transition.opacity>
                            <x-loading-indicator>{{ __('monitoring.detail.no_data') }}</x-loading-indicator>
                        </div>
                    </x-container>

                    <template x-if="responseStats[responseTimeRange + 'd']">
                        <div class="mb-4 grid grid-cols-1 gap-4 text-center md:grid-cols-2 2xl:grid-cols-3">
                            <x-container>
                                <x-paragraph class="text-gray-500">{{ __('monitoring.detail.response_time.min') }}</x-paragraph>
                                <x-paragraph
                                    class="text-xl font-semibold text-gray-800"
                                    x-text="
                                        responseStats[responseTimeRange + 'd']?.avg !== undefined
                                            ? Math.round(responseStats[responseTimeRange + 'd'].avg) + ' ms'
                                            : '—'
                                    "
                                >
                                    —
                                </x-paragraph>
                            </x-container>
                            <x-container>
                                <x-paragraph class="text-gray-500">{{ __('monitoring.detail.response_time.avg') }}</x-paragraph>
                                <x-paragraph
                                    class="text-xl font-semibold text-gray-800"
                                    x-text="
                                        responseStats[responseTimeRange + 'd']?.avg !== undefined
                                            ? Math.round(responseStats[responseTimeRange + 'd'].avg) + ' ms'
                                            : '—'
                                    "
                                >
                                    —
                                </x-paragraph>
                            </x-container>
                            <x-container>
                                <x-paragraph class="text-gray-500">{{ __('monitoring.detail.response_time.max') }}</x-paragraph>
                                <x-paragraph
                                    class="text-xl font-semibold text-gray-800"
                                    x-text="
                                        responseStats[responseTimeRange + 'd']?.max !== undefined
                                            ? Math.round(responseStats[responseTimeRange + 'd'].max) + ' ms'
                                            : '—'
                                    "
                                >
                                    —
                                </x-paragraph>
                            </x-container>
                        </div>
                    </template>
                @endif

                <div id="incidents" class="mt-4">
                    <div class="mb-2 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <x-heading type="h2" class="text-lg font-semibold text-gray-800"
                            >{{ __('monitoring.detail.incidents.heading') }}
                        </x-heading>

                        <div>
                            <label for="incidents-range" class="hidden">{{ __('monitoring.filter.heading') }}</label>

                            <select
                                id="incidents-range"
                                x-model="incidentsRange"
                                @change="loadIncidents(incidentsRange)"
                                class="focus:ring-opacity-50 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            >
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

                    <template x-if="! incidentsLoading && incidents.length === 0">
                        <x-paragraph class="text-gray-500">{{ __('monitoring.detail.incidents.no_incidents') }}</x-paragraph>
                    </template>

                    <template x-if="! incidentsLoading && incidents.length > 0">
                        <template x-for="incident in incidents" :key="incident.down_at">
                            <x-container space="true">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <x-span class="block text-gray-500">{{ __('monitoring.detail.incidents.incident.down_at') }}</x-span>
                                        <x-span
                                            class="font-medium text-red-600 dark:text-red-600"
                                            x-text="incident.down_at"
                                        ></x-span>
                                    </div>
                                    <div x-show="incident.up_at">
                                        <x-span class="block text-gray-500">{{ __('monitoring.detail.incidents.incident.up_at') }}</x-span>
                                        <x-span
                                            class="font-medium text-green-600 dark:text-green-600"
                                            x-text="incident.up_at"
                                        ></x-span>
                                    </div>
                                    <div x-show="incident.duration">
                                        <x-span class="block text-gray-500">{{ __('monitoring.detail.incidents.incident.duration') }}</x-span>
                                        <x-span
                                            class="font-medium text-gray-800 dark:text-gray-400"
                                            x-text="incident.duration"
                                        ></x-span>
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
                            <x-paragraph
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                x-show="! recentChecksLoading && recentChecks.length > 0"
                            >
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

                    <template x-if="! recentChecksLoading && recentChecks.length === 0">
                        <x-paragraph class="text-gray-500">{{ __('monitoring.detail.checks.no_checks') }}</x-paragraph>
                    </template>

                    <template x-if="! recentChecksLoading && recentChecks.length > 0">
                        <div>
                            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <div class="hidden grid-cols-[minmax(13rem,1.25fr)_minmax(0,3fr)_auto] gap-4 border-b border-gray-100 bg-gray-50/80 px-5 py-3 text-xs font-semibold text-gray-500 uppercase xl:grid dark:border-gray-700 dark:bg-gray-900/35 dark:text-gray-400">
                                    <span>{{ __('monitoring.detail.checks.labels.checked_at') }}</span>
                                    <span>{{ __('monitoring.detail.checks.labels.result') }}</span>
                                    <span class="text-right">{{ __('monitoring.detail.checks.labels.status') }}</span>
                                </div>

                                <div class="divide-y divide-gray-100 dark:divide-gray-700/80">
                                    <template x-for="(check, index) in recentChecks" :key="check.id">
                                        <div
                                            data-recent-check-row
                                            class="grid gap-4 px-4 py-4 transition duration-150 hover:bg-gray-50/80 sm:px-5 xl:grid-cols-[minmax(13rem,1.25fr)_minmax(0,3fr)_auto] xl:items-center dark:hover:bg-gray-900/30"
                                        >
                                            <div class="flex min-w-0 gap-3">
                                                <div class="relative flex w-5 shrink-0 justify-center">
                                                    <span class="mt-1 flex size-5 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                        <svg class="size-3.5" viewBox="0 0 16 16" aria-hidden="true">
                                                            <path
                                                                fill="currentColor"
                                                                d="M6.35 11.15 2.9 7.7l1.05-1.05 2.4 2.4 5.7-5.7L13.1 4.4z"
                                                            />
                                                        </svg>
                                                    </span>
                                                    <span
                                                        class="absolute top-7 bottom-[-1rem] w-px bg-emerald-100 dark:bg-emerald-900"
                                                        x-show="index < recentChecks.length - 1"
                                                    ></span>
                                                </div>
                                                <div class="min-w-0">
                                                    <time
                                                        class="block truncate text-sm font-semibold text-gray-950 dark:text-gray-100"
                                                        x-bind:datetime="check.checkedAtIso"
                                                        x-bind:title="check.checkedAt"
                                                        x-text="check.checkedAt"
                                                    ></time>
                                                </div>
                                            </div>

                                            <div
                                                data-recent-check-result
                                                class="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_minmax(0,0.7fr)_minmax(0,0.7fr)]"
                                                x-bind:class="
                                                    hasServerHealthMetrics(check.serverHealthMetrics)
                                                        ? '2xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_minmax(0,1.2fr)_minmax(0,0.7fr)_minmax(0,0.7fr)]'
                                                        : ''
                                                "
                                            >
                                                <div class="min-w-0">
                                                    <span class="block text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                                        {{ __('monitoring.detail.checks.labels.status_code') }}
                                                    </span>
                                                    <span
                                                        class="mt-1 block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                                        x-text="check.httpStatusCode ?? '{{ __('monitoring.detail.checks.status_code_unavailable') }}'"
                                                    ></span>
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="flex min-w-0 items-baseline justify-between gap-3">
                                                        <span class="block min-w-0 truncate text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                                            {{ __('monitoring.detail.checks.labels.response_time') }}
                                                        </span>
                                                        <span
                                                            class="shrink-0 text-sm font-semibold text-sky-700 dark:text-sky-300"
                                                            x-text="formatResponseTime(check.responseTime)"
                                                        ></span>
                                                    </div>
                                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                                        <div
                                                            class="h-full rounded-full bg-sky-500 dark:bg-sky-400"
                                                            x-bind:style="
                                                                `width: ${responseTimeBarWidth(check.responseTime)}%`
                                                            "
                                                        ></div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="min-w-0"
                                                    x-show="hasServerHealthMetrics(check.serverHealthMetrics)"
                                                >
                                                    <span class="block text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                                        {{ __('monitoring.detail.checks.labels.server_health') }}
                                                    </span>
                                                    <span
                                                        class="mt-1 block truncate text-sm text-gray-900 dark:text-gray-100"
                                                        x-text="formatServerHealthMetrics(check.serverHealthMetrics)"
                                                    ></span>
                                                </div>

                                                <div class="min-w-0">
                                                    <span class="block text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                                        {{ __('monitoring.detail.checks.labels.source') }}
                                                    </span>
                                                    <span
                                                        class="mt-1 block text-sm text-gray-900 dark:text-gray-100"
                                                        x-text="resolveCheckSourceLabel(check.source)"
                                                    ></span>
                                                </div>

                                                <div class="min-w-0">
                                                    <span class="block text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                                        {{ __('monitoring.detail.checks.labels.raw_status') }}
                                                    </span>
                                                    <span
                                                        class="mt-1 block truncate text-sm font-semibold text-gray-900 uppercase dark:text-gray-100"
                                                        x-text="check.status"
                                                    ></span>
                                                </div>
                                            </div>

                                            <div
                                                data-recent-check-status
                                                class="flex min-w-0 justify-start lg:justify-end"
                                            >
                                                <span
                                                    class="inline-flex max-w-full items-center truncate rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap uppercase"
                                                    x-bind:class="resolveCheckStatusClass(check.statusIdentifier)"
                                                    x-text="resolveCheckStatusLabel(check.statusIdentifier)"
                                                ></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="pt-5 text-center" x-show="recentChecksHasMore">
                                <button
                                    type="button"
                                    @click="loadMoreChecks()"
                                    class="inline-flex items-center rounded-md border border-purple-200 bg-white px-4 py-2 text-sm font-semibold text-purple-700 uppercase shadow-sm transition duration-150 hover:border-purple-300 hover:bg-purple-50 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-60 dark:border-purple-800 dark:bg-gray-800 dark:text-purple-300 dark:hover:bg-purple-950/40"
                                    x-bind:disabled="recentChecksLoadingMore"
                                    x-bind:class="{ 'opacity-60 cursor-not-allowed': recentChecksLoadingMore }"
                                >
                                    <span x-text="recentChecksLoadingMore ? '{{ __('monitoring.detail.checks.loading_more') }}' : '{{ __('monitoring.detail.checks.load_more') }}'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <aside data-monitoring-context-rail class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                <x-dashboard.panel
                    :heading="__('monitoring.detail.context.ownership')"
                    :description="__('monitoring.detail.context.ownership_description')"
                >
                    <div class="space-y-4 p-5">
                        <div>
                            <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                                {{ __('monitoring.detail.context.owner') }}
                            </p>
                            <p class="mt-1 font-bold text-gray-950 dark:text-white">
                                {{ $monitoring->team?->name ?? $monitoring->user?->name ?? __('monitoring.detail.context.unknown') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $monitoring->team_id ? __('monitoring.detail.context.team') : __('monitoring.detail.context.private') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                                {{ __('monitoring.detail.context.groups') }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($monitoring->groups as $group)
                                    <span class="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">{{ $group->name }}</span>
                                @empty
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.context.no_groups') }}</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </x-dashboard.panel>

                <x-dashboard.panel :heading="__('monitoring.detail.context.domain_ssl')">
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @if ($monitoring->sslResult)
                            <div class="p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-gray-950 dark:text-white">
                                        {{ __('monitoring.detail.ssl.heading') }}
                                    </p>
                                    <span class="h-2.5 w-2.5 rounded-full {{ $monitoring->sslResult->is_valid ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if ($monitoring->sslResult->expires_at)
                                        {{ __('monitoring.detail.context.valid_until_label') }}
                                        <x-date-time :value="$monitoring->sslResult->expires_at" format="date" />
                                    @else
                                        {{ __('monitoring.detail.context.no_expiry') }}
                                    @endif
                                </p>
                            </div>
                        @endif
                        @if ($monitoring->domainResult)
                            <div class="p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-gray-950 dark:text-white">
                                        {{ __('monitoring.detail.domain.heading') }}
                                    </p>
                                    <span class="h-2.5 w-2.5 rounded-full {{ $monitoring->domainResult->is_valid ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if ($monitoring->domainResult->expires_at)
                                        {{ __('monitoring.detail.context.valid_until_label') }}
                                        <x-date-time :value="$monitoring->domainResult->expires_at" format="date" />
                                    @else
                                        {{ __('monitoring.detail.context.no_expiry') }}
                                    @endif
                                </p>
                            </div>
                        @endif
                        @if (! $monitoring->sslResult && ! $monitoring->domainResult)
                            <p class="p-5 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('monitoring.detail.context.not_available') }}
                            </p>
                        @endif
                    </div>
                </x-dashboard.panel>

                <x-dashboard.panel :heading="__('monitoring.detail.context.regions')">
                    <div class="flex flex-wrap gap-2 p-5">
                        @forelse ($monitoring->preferredLocationCodes() as $location)
                            <span class="inline-flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-800 dark:bg-gray-900/60 dark:text-gray-200">
                                <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                {{ $location }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.context.no_regions') }}</span>
                        @endforelse
                    </div>
                </x-dashboard.panel>

                <x-dashboard.panel :heading="__('monitoring.detail.context.maintenance')">
                    <div class="p-5">
                        @if ($monitoring->maintenance_from)
                            <p class="font-bold text-gray-950 dark:text-white">
                                {{ $monitoring->isUnderMaintenance() ? __('monitoring.detail.context.maintenance_active') : __('monitoring.detail.context.maintenance_scheduled') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <x-date-time :value="$monitoring->maintenance_from" />
                                @if ($monitoring->maintenance_until)
                                    –
                                    <x-date-time :value="$monitoring->maintenance_until" />
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('monitoring.detail.context.no_maintenance') }}
                            </p>
                        @endif
                    </div>
                </x-dashboard.panel>

                <x-dashboard.panel :heading="__('monitoring.detail.context.notifications')">
                    <div class="space-y-4 p-5">
                        <div>
                            <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                                {{ __('monitoring.detail.context.recipients') }}
                            </p>
                            <div class="mt-2 space-y-2">
                                @foreach ($notificationRecipients as $recipient)
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-purple-100 text-xs font-black text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">{{ mb_strtoupper(mb_substr($recipient->name, 0, 1)) }}</span>
                                        <span class="truncate">{{ $recipient->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold tracking-[0.14em] text-gray-400 uppercase dark:text-gray-500">
                                {{ __('monitoring.detail.context.channels') }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($notificationChannels as $channel)
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ __('notifications.channels.' . $channel) }}</span>
                                @empty
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.context.no_channels') }}</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </x-dashboard.panel>

                <x-dashboard.panel :heading="__('monitoring.detail.context.status_pages')">
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($statusPages as $statusPage)
                            <a
                                href="{{ route('status-pages.show', $statusPage) }}"
                                class="flex items-center justify-between gap-3 p-5 text-sm transition hover:bg-purple-50 dark:hover:bg-purple-950/20"
                            >
                                <span class="min-w-0 truncate font-bold text-gray-900 dark:text-white">{{ $statusPage->name }}</span>
                                <span class="shrink-0 text-xs font-semibold text-purple-700 dark:text-purple-300">{{ $statusPage->is_public ? __('monitoring.detail.context.public') : __('monitoring.detail.context.private') }}</span>
                            </a>
                        @empty
                            <p class="p-5 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('monitoring.detail.context.no_status_pages') }}
                            </p>
                        @endforelse
                    </div>
                </x-dashboard.panel>
            </aside>
        </div>
    </x-main>
</x-app-layout>
