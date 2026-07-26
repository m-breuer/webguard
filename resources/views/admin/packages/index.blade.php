<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.packages.title') }}
        </x-heading>
    </x-slot>

    <x-main>
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
        <x-async-table id="admin-packages-table" :endpoint="route('admin.packages.index')" :paginator="$packages"
            :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
            :initial-direction="$direction"
            search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('admin.packages.title')]) }}">
            <x-slot name="actions">
                <x-primary-button :href="route('admin.packages.create')" class="sm:ml-auto"
                    data-form-modal-trigger data-form-modal-name="admin-package-form-modal">
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

        <x-form-modal name="admin-package-form-modal" title="{{ __('admin.packages.title') }}"
            :show="in_array($modalForm, ['admin-package-create', 'admin-package-edit'], true)">
            <div class="p-6" x-ref="content">
                @if ($modalForm === 'admin-package-create')
                    @include('admin.packages._modal-form', [
                        'action' => route('admin.packages.store'),
                        'modalForm' => 'admin-package-create',
                    ])
                @elseif ($modalForm === 'admin-package-edit' && $modalPackage)
                    @include('admin.packages._modal-form', [
                        'action' => route('admin.packages.update', $modalPackage),
                        'package' => $modalPackage,
                        'modalForm' => 'admin-package-edit',
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
