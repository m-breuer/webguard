<x-app-layout>
    <x-slot name="header">
        <x-heading>{{ __('api.logs.title') }}</x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-async-table id="admin-api-logs-table" :endpoint="route('admin.apis.index')" :paginator="$apiLogs"
            :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
            :initial-direction="$direction"
            search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('api.logs.title')]) }}">
            <x-slot name="head">
                <x-table.heading sort="created_at">{{ __('api.logs.fields.date') }}</x-table.heading>
                <x-table.heading sort="email">{{ __('user.fields.email') }}</x-table.heading>
                <x-table.heading sort="route">{{ __('api.logs.fields.endpoint') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @include('admin.api.partials.rows', ['apiLogs' => $apiLogs])
            </x-slot>
        </x-async-table>
    </x-main>
</x-app-layout>
