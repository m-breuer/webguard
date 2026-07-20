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
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
        <x-async-table id="admin-users-table" :endpoint="route('admin.users.index')" :paginator="$users"
            :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
            :initial-direction="$direction"
            search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('user.title')]) }}">
            <x-slot name="actions">
                <x-primary-button :href="route('admin.users.create')" class="sm:ml-auto"
                    data-form-modal-trigger data-form-modal-name="admin-user-form-modal">
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

        <x-form-modal name="admin-user-form-modal" title="{{ __('user.title') }}"
            :show="in_array($modalForm, ['admin-user-create', 'admin-user-edit'], true)">
            <div class="p-6" x-ref="content">
                @if ($modalForm === 'admin-user-create')
                    @include('admin.users._modal-form', [
                        'action' => route('admin.users.store'),
                        'modalForm' => 'admin-user-create',
                    ])
                @elseif ($modalForm === 'admin-user-edit' && $modalUser)
                    @include('admin.users._modal-form', [
                        'action' => route('admin.users.update', $modalUser),
                        'user' => $modalUser,
                        'packages' => $modalPackages,
                        'modalForm' => 'admin-user-edit',
                    ])
                @else
                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                @endif
            </div>
        </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
