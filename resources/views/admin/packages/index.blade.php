<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.packages.title') }}
        </x-heading>
    </x-slot>

    <x-main>
        <x-async-table id="admin-packages-table" :endpoint="route('admin.packages.index')" :paginator="$packages"
            :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
            :initial-direction="$direction"
            search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('admin.packages.title')]) }}">
            <x-slot name="actions">
                <x-primary-button :href="route('admin.packages.create')" class="sm:ml-auto">
                    {{ __('button.create') }}
                </x-primary-button>
            </x-slot>

            <x-slot name="head">
                <x-table.heading sort="monitoring_limit">{{ __('admin.packages.fields.monitoring_limit') }}</x-table.heading>
                <x-table.heading sort="price">{{ __('admin.packages.fields.price') }}</x-table.heading>
                <x-table.heading sort="is_selectable">{{ __('admin.packages.fields.is_selectable') }}</x-table.heading>
                <x-table.heading>{{ __('admin.packages.fields.actions') }}</x-table.heading>
            </x-slot>
            <x-slot name="body">
                @include('admin.packages.partials.rows', ['packages' => $packages])
            </x-slot>
        </x-async-table>
    </x-main>
</x-app-layout>
