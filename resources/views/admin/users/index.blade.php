<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('user.title') }}
        </x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-async-table id="admin-users-table" :endpoint="route('admin.users.index')" :paginator="$users"
            :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
            :initial-direction="$direction"
            search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('user.title')]) }}">
            <x-slot name="actions">
                <x-primary-button :href="route('admin.users.create')" class="sm:ml-auto">
                    {{ __('button.create') }}
                </x-primary-button>
            </x-slot>

            <x-slot name="head">
                <x-table.heading sort="name">{{ __('user.fields.name') }}</x-table.heading>
                <x-table.heading sort="email">{{ __('user.fields.email') }}</x-table.heading>
                <x-table.heading sort="email_verified_at">{{ __('user.fields.email_verification') }}</x-table.heading>
                <x-table.heading sort="role">{{ __('user.fields.role') }}</x-table.heading>
                <x-table.heading sort="monitoring_limit">{{ __('user.fields.monitoring_limit') }}</x-table.heading>
                <x-table.heading sort="created_at">{{ __('user.fields.created_at') }}</x-table.heading>
                <x-table.heading sort="updated_at">{{ __('user.fields.updated_at') }}</x-table.heading>
                <x-table.heading>{{ __('user.actions.edit') . ' / ' . __('button.delete') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @include('admin.users.partials.rows', ['users' => $users])
            </x-slot>
        </x-async-table>
    </x-main>
</x-app-layout>
