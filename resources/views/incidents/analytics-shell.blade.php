<x-app-layout>
    <x-slot name="header">
        <x-monitoring-operations-header />
    </x-slot>

    <x-main>
        <div
            id="incident-analytics-page-content"
            data-incident-analytics-loader
            data-endpoint="{{ route('incidents.analytics', request()->query()) }}"
            data-error="{{ __('search.messages.error') }}"
            x-data="incidentAnalyticsLoader()"
            class="space-y-6"
        >
            <x-container>
                <div class="flex items-center gap-3" x-show="loading">
                    <span class="h-3 w-3 animate-pulse rounded-full bg-purple-600" aria-hidden="true"></span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                </div>
                <p x-show="error" x-text="error" class="text-sm font-semibold text-red-600 dark:text-red-300" x-cloak></p>
            </x-container>
        </div>
    </x-main>
</x-app-layout>
