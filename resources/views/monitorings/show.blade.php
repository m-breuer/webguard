@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1" class="flex flex-wrap items-baseline">
            {{ $monitoring->name }}:
            <x-span class="ml-2">{{ $monitoring->target }}</x-span>
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

        <div class="ml-auto flex flex-wrap items-start gap-2 sm:items-center">

            @if (!Auth::user()->isGuest())
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
                            class="block px-4 py-2 text-left text-gray-700 hover:bg-gray-100 sm:text-right">
                            {{ __('monitoring.actions.edit') }}
                        </a>
                        <form method="POST" action="{{ route('monitorings.destroyResults', $monitoring) }}"
                            onsubmit="return confirm('{{ __('monitoring.actions.reset.confirmation') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="block w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 sm:text-right">
                                {{ __('monitoring.actions.reset.heading') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('monitorings.destroy', $monitoring) }}"
                            onsubmit="return confirm('{{ __('monitoring.actions.delete.confirmation') }}')">
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
        </div>
    </x-slot>


    <x-main x-init="loadStatus();
    loadHeatmap();
    loadUptime();
    loadCustomRangeStats();
    loadPerformanceChart(selectedRange);
    loadIncidents(selectedRange);
    loadChecks(selectedRange);
    initializeDeferredLoads();" x-data="Object.assign({
        selectedRange: 1
    }, monitoringDetail('{{ $monitoring->id }}', {
        min: '{{ __('monitoring.detail.response_time.min_label') }}',
        avg: '{{ __('monitoring.detail.response_time.avg_label') }}',
        max: '{{ __('monitoring.detail.response_time.max_label') }}',
        yAxis: '{{ __('monitoring.detail.response_time.y_axis_label') }}',
        xAxis: '{{ __('monitoring.detail.response_time.x_axis_label') }}',
        customRangeInvalidDate: '{{ __('monitoring.detail.custom_range.errors.invalid_date_range') }}',
        customRangeLoadError: '{{ __('monitoring.detail.custom_range.errors.load_failed') }}',
        checkStatusSuccess: '{{ __('monitoring.detail.checks.statuses.success') }}',
        checkStatusRedirect: '{{ __('monitoring.detail.checks.statuses.redirect') }}',
        checkStatusClientError: '{{ __('monitoring.detail.checks.statuses.client_error') }}',
        checkStatusServerError: '{{ __('monitoring.detail.checks.statuses.server_error') }}',
        checkStatusUnknown: '{{ __('monitoring.detail.checks.statuses.unknown') }}',
        checkStatusMaintenance: '{{ __('monitoring.detail.checks.statuses.maintenance') }}',
        checkSourceLive: '{{ __('monitoring.detail.checks.sources.live') }}',
        checkSourceArchived: '{{ __('monitoring.detail.checks.sources.archived') }}',
        checkResponseTimeUnavailable: '{{ __('monitoring.detail.checks.response_time_unavailable') }}',
    }))">

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

            <x-container id="uptime-card-custom-range">
                <x-heading type="h2">{{ __('monitoring.detail.custom_range.heading') }}</x-heading>
                <x-paragraph class="mt-2 text-sm text-gray-500">
                    {{ __('monitoring.detail.custom_range.help') }}
                </x-paragraph>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <x-input-label for="custom-range-from" :value="__('monitoring.detail.custom_range.from')" />
                        <input id="custom-range-from" type="date" x-model="customRangeFrom" :max="customRangeUntil"
                            max="{{ now()->toDateString() }}" @change="loadCustomRangeStats()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <x-input-label for="custom-range-until" :value="__('monitoring.detail.custom_range.until')" />
                        <input id="custom-range-until" type="date" x-model="customRangeUntil" :min="customRangeFrom"
                            max="{{ now()->toDateString() }}" @change="loadCustomRangeStats()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>
                </div>

                <template x-if="customRangeStatsLoading">
                    <div class="mt-4" x-transition.opacity>
                        <x-loading-indicator>{{ __('monitoring.detail.custom_range.loading') }}</x-loading-indicator>
                    </div>
                </template>

                <template x-if="!customRangeStatsLoading && customRangeStatsError">
                    <x-paragraph class="mt-4 text-sm font-medium text-red-600" x-text="customRangeStatsError"></x-paragraph>
                </template>

                <template x-if="!customRangeStatsLoading && !customRangeStatsError">
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <x-paragraph class="text-sm text-gray-500">{{ __('monitoring.detail.custom_range.uptime') }}</x-paragraph>
                            <x-paragraph class="text-2xl font-bold text-purple-600"
                                x-text="customRangeStats?.hasData && customRangeStats.uptimePercentage !== null
                                    ? customRangeStats.uptimePercentage.toFixed(2) + '%'
                                    : '—'">—</x-paragraph>
                        </div>
                        <div>
                            <x-paragraph class="text-sm text-gray-500">{{ __('monitoring.detail.custom_range.incidents') }}</x-paragraph>
                            <x-paragraph class="text-2xl font-semibold"
                                x-text="customRangeStats ? customRangeStats.incidentsCount : '—'">—</x-paragraph>
                        </div>
                    </div>
                </template>
            </x-container>
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

        @if ($monitoring->type !== MonitoringType::PING)
            <div class="mb-2 flex items-center justify-between">
                <x-heading type="h2">{{ __('monitoring.detail.response_time.heading') }}</x-heading>

                <div>
                    <label for="range" class="hidden">{{ __('monitoring.filter.heading') }}</label>

                    <select x-model="selectedRange"
                        @change="loadPerformanceChart(selectedRange); loadIncidents(selectedRange); loadChecks(selectedRange);"
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

            <template x-if="responseStats[selectedRange + 'd']">
                <div class="mb-4 grid grid-cols-1 gap-4 text-center md:grid-cols-3">
                    <x-container>
                        <x-paragraph
                            class="text-gray-500">{{ __('monitoring.detail.response_time.min') }}</x-paragraph>
                        <x-paragraph class="text-xl font-semibold text-gray-800"
                            x-text="responseStats[selectedRange + 'd']?.avg !== undefined ? Math.round(responseStats[selectedRange + 'd'].avg) + ' ms' : '—'">
                            —
                        </x-paragraph>
                    </x-container>
                    <x-container>
                        <x-paragraph
                            class="text-gray-500">{{ __('monitoring.detail.response_time.avg') }}</x-paragraph>
                        <x-paragraph class="text-xl font-semibold text-gray-800"
                            x-text="responseStats[selectedRange + 'd']?.avg !== undefined ? Math.round(responseStats[selectedRange + 'd'].avg) + ' ms' : '—'">
                            —
                        </x-paragraph>
                    </x-container>
                    <x-container>
                        <x-paragraph
                            class="text-gray-500">{{ __('monitoring.detail.response_time.max') }}</x-paragraph>
                        <x-paragraph class="text-xl font-semibold text-gray-800"
                            x-text="responseStats[selectedRange + 'd']?.max !== undefined ? Math.round(responseStats[selectedRange + 'd'].max) + ' ms' : '—'">
                            —
                        </x-paragraph>
                    </x-container>
                </div>
            </template>
        @endif

        <div id="incidents" class="mt-4">
            <x-heading type="h2"
                class="mb-2 text-lg font-semibold text-gray-800">{{ __('monitoring.detail.incidents.heading') }}
            </x-heading>

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
            <div class="mb-2 flex items-center justify-between gap-4">
                <x-heading type="h2" class="text-lg font-semibold text-gray-800">
                    {{ __('monitoring.detail.checks.heading') }}
                </x-heading>
                <x-paragraph class="text-sm text-gray-500">
                    {{ __('monitoring.detail.checks.help') }}
                </x-paragraph>
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
                <div class="space-y-3">
                    <template x-for="check in recentChecks" :key="check.id">
                        <x-container space="true">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <x-paragraph class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="check.checkedAt"></x-paragraph>
                                    <x-paragraph class="text-sm text-gray-500"
                                        x-text="check.checkedAtHuman"></x-paragraph>
                                </div>
                                <x-span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                                    x-bind:class="resolveCheckStatusClass(check.statusIdentifier)"
                                    x-text="resolveCheckStatusLabel(check.statusIdentifier)"></x-span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                                <div>
                                    <x-span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ __('monitoring.detail.checks.labels.status_code') }}
                                    </x-span>
                                    <x-span class="text-sm text-gray-800 dark:text-gray-200"
                                        x-text="check.httpStatusCode ?? '{{ __('monitoring.detail.checks.status_code_unavailable') }}'"></x-span>
                                </div>
                                <div>
                                    <x-span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ __('monitoring.detail.checks.labels.response_time') }}
                                    </x-span>
                                    <x-span class="text-sm text-gray-800 dark:text-gray-200"
                                        x-text="formatResponseTime(check.responseTime)"></x-span>
                                </div>
                                <div>
                                    <x-span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ __('monitoring.detail.checks.labels.source') }}
                                    </x-span>
                                    <x-span class="text-sm text-gray-800 dark:text-gray-200"
                                        x-text="resolveCheckSourceLabel(check.source)"></x-span>
                                </div>
                                <div>
                                    <x-span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ __('monitoring.detail.checks.labels.raw_status') }}
                                    </x-span>
                                    <x-span class="text-sm uppercase text-gray-800 dark:text-gray-200"
                                        x-text="check.status"></x-span>
                                </div>
                            </div>
                        </x-container>
                    </template>
                </div>
            </template>
        </div>
    </x-main>
</x-app-layout>
