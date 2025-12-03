<x-public-layout>
    <x-slot name="head">
        <meta name="robots" content="noindex">
        <title>{{ __('monitoring.public_label.title', ['monitoringName' => $monitoring->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <x-heading>
            {{ $monitoring->name }}
            <small>({{ strtoupper($monitoring->type->value) }})</small>

            <a href="{{ $monitoring->target }}" target="_blank" title="{{ $monitoring->name }}"
                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-white">
                {{ $monitoring->target }}
            </a>
        </x-heading>
    </x-slot>

    <x-main>
        <div x-data="uptimeCalendar('{{ $monitoring->id }}')" x-init="fetchUptimeCalendar">
            <template x-if="isLoading">
                <p>{{ __('calendar.loading') }}</p>
            </template>

            <template x-if="!isLoading && calendarData">
                <div x-data="{ data: calendarData }">
                    @include('components.monitoring-calendar')
                </div>
            </template>
        </div>
    </x-main>
</x-public-layout>
