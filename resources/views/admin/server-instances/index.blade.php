<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1"> {{ __('admin.server_instances.title') }} </x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-container>
                <x-heading type="h2">{{ __('admin.server_instances.summary.total_instances') }}</x-heading>
                <p class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-300">
                    {{ $summary['total_instances'] }}
                </p>
            </x-container>
            <x-container>
                <x-heading type="h2">{{ __('admin.server_instances.summary.active_instances') }}</x-heading>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $summary['active_instances'] }}
                </p>
            </x-container>
            <x-container>
                <x-heading type="h2">{{ __('admin.server_instances.summary.stale_instances') }}</x-heading>
                <p class="mt-2 text-2xl font-bold text-yellow-600 dark:text-yellow-300">
                    {{ $summary['stale_instances'] }}
                </p>
            </x-container>
            <x-container>
                <x-heading type="h2">{{ __('admin.server_instances.summary.total_monitorings') }}</x-heading>
                <p class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-300">
                    {{ $summary['total_monitorings'] }}
                </p>
            </x-container>
        </div>

        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            <x-async-table
                id="admin-server-instances-table"
                :endpoint="route('admin.server-instances.index')"
                :paginator="$instances"
                :filters="$filters"
                :initial-filters="$activeFilters"
                :initial-sort="$sort"
                :initial-direction="$direction"
                search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('admin.server_instances.title')]) }}"
            >
                <x-slot name="actions">
                    <x-primary-button
                        :href="route('admin.server-instances.create')"
                        class="sm:ml-auto"
                        data-form-modal-trigger
                        data-form-modal-name="admin-server-instance-form-modal"
                    >
                        {{ __('button.create') }}
                    </x-primary-button>
                </x-slot>

                <x-slot name="head">
                    <x-table.heading sort="code">{{ __('admin.server_instances.fields.code') }}</x-table.heading>
                    <x-table.heading sort="is_active">{{ __('admin.server_instances.fields.status') }}</x-table.heading>
                    <x-table.heading>{{ __('admin.server_instances.fields.health') }}</x-table.heading>
                    <x-table.heading sort="last_seen_at">
                        {{ __('admin.server_instances.fields.last_seen_at') }}</x-table.heading>
                    <x-table.heading>{{ __('admin.server_instances.fields.monitorings') }}</x-table.heading>
                    <x-table.heading>{{ __('admin.server_instances.fields.monitoring_types') }}</x-table.heading>
                    <x-table.heading sort="created_at">
                        {{ __('admin.server_instances.fields.created_at') }}</x-table.heading>
                    <x-table.heading sort="updated_at">
                        {{ __('admin.server_instances.fields.updated_at') }}</x-table.heading>
                    <x-table.heading>{{ __('admin.server_instances.fields.actions') }}</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @include('admin.server-instances.partials.rows', [
                        'instances' => $instances,
                        'monitoringCounts' => $monitoringCounts,
                        'monitoringTypeCounts' => $monitoringTypeCounts,
                    ])
                </x-slot>
            </x-async-table>

            <x-form-modal
                name="admin-server-instance-form-modal"
                title="{{ __('admin.server_instances.title') }}"
                :show="in_array($modalForm, ['admin-server-instance-create', 'admin-server-instance-edit'], true)"
            >
                <div class="p-6" x-ref="content">
                    @if ($modalForm === 'admin-server-instance-create')
                        @include('admin.server-instances._modal-form', [
                            'action' => route('admin.server-instances.store'),
                            'modalForm' => 'admin-server-instance-create',
                        ])
                    @elseif ($modalForm === 'admin-server-instance-edit' && $modalInstance)
                        @include('admin.server-instances._modal-form', [
                            'action' => route('admin.server-instances.update', $modalInstance),
                            'instance' => $modalInstance,
                            'modalForm' => 'admin-server-instance-edit',
                        ])
                    @else
                        <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                        <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                        <div x-html="content"></div>
                    @endif
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
