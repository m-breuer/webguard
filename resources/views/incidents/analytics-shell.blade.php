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
            <div class="space-y-6">
                @foreach (['overview', 'groups', 'status-pages', 'incidents'] as $section)
                    <section
                        data-analytics-section="{{ $section }}"
                        data-endpoint="{{ route('incidents.analytics', request()->query()) }}"
                        data-error="{{ __('search.messages.error') }}"
                        aria-live="polite"
                    >
                        <x-container>
                            <div data-section-loading>
                                <x-loading-skeleton variant="section" />
                            </div>
                            <p data-section-error class="text-sm font-semibold text-red-600 dark:text-red-300" hidden>
                                {{ __('search.messages.error') }}
                            </p>
                        </x-container>
                    </section>
                @endforeach
            </div>
        </div>
    </x-main>
</x-app-layout>
