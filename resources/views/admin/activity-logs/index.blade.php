<x-app-layout>
    <x-slot name="header">
        <x-heading>{{ __('admin.activity_logs.title') }}</x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-async-table id="admin-activity-logs-table" :endpoint="route('admin.activity-logs.index')"
            :paginator="$activities" :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
            :initial-direction="$direction"
            search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('admin.activity_logs.title')]) }}">
            <x-slot name="head">
                <x-table.heading sort="created_at">{{ __('admin.activity_logs.fields.created_at') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.actor') }}</x-table.heading>
                <x-table.heading sort="log_name">{{ __('admin.activity_logs.fields.log_name') }}</x-table.heading>
                <x-table.heading sort="event">{{ __('admin.activity_logs.fields.event') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.subject') }}</x-table.heading>
                <x-table.heading sort="description">{{ __('admin.activity_logs.fields.description') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.changes') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @include('admin.activity-logs.partials.rows', [
                    'activities' => $activities,
                    'subjectTypes' => $subjectTypes,
                ])
            </x-slot>
        </x-async-table>
    </x-main>
</x-app-layout>
