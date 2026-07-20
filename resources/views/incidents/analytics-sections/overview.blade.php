<div data-analytics-section-content>
    @php
        $summary = $overview['summary'];
        $state = $overview['overallState'];
        $healthTitle = __('incidents.analytics.overview.health.' . $state);
    @endphp
    <x-container class="overflow-hidden" id="overview">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $healthTitle }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.overview.health.updated') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:grid-cols-4">
                <div><p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.total') }}</p><p class="mt-1 text-lg font-semibold">{{ $summary['total'] }}</p></div>
                <div><p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.healthy') }}</p><p class="mt-1 text-lg font-semibold text-green-600">{{ $summary['healthy'] }}</p></div>
                <div><p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.down') }}</p><p class="mt-1 text-lg font-semibold text-red-600">{{ $summary['down'] }}</p></div>
                <div><p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.unknown') }}</p><p class="mt-1 text-lg font-semibold text-yellow-600">{{ $summary['unknown'] }}</p></div>
            </div>
        </div>
    </x-container>
</div>
