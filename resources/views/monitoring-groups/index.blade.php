<x-app-layout>
    <x-slot name="header">
        <x-monitoring-operations-header>
            @if (! Auth::user()->isDemo())
                <x-slot name="actions">
                    <x-primary-button
                        :href="route('monitoring-groups.create')"
                        data-form-modal-trigger
                        data-form-modal-name="monitoring-group-form-modal"
                    >
                        {{ __('button.create') }}
                    </x-primary-button>
                </x-slot>
            @endif
        </x-monitoring-operations-header>
    </x-slot>

    <x-main>
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            @if ($monitoringGroups->isEmpty())
                <x-container class="text-center">
                    <x-heading type="h2">{{ __('monitoring_group.empty.title') }}</x-heading>
                    <x-paragraph space="true">{{ __('monitoring_group.empty.text') }}</x-paragraph>
                    @if (! Auth::user()->isDemo())
                        <x-primary-button
                            :href="route('monitoring-groups.create')"
                            data-form-modal-trigger
                            data-form-modal-name="monitoring-group-form-modal"
                        >
                            {{ __('button.create') }}
                        </x-primary-button>
                    @endif
                </x-container>
            @else
                <div class="space-y-4">
                    @foreach ($monitoringGroups as $monitoringGroup)
                        <x-container>
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <x-heading type="h2">{{ $monitoringGroup->name }}</x-heading>
                                    @if ($monitoringGroup->description)
                                        <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                                            {{ $monitoringGroup->description }}
                                        </p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ trans_choice('monitoring_group.monitorings_count', $monitoringGroup->monitorings_count, ['count' => $monitoringGroup->monitorings_count]) }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if (! Auth::user()->isDemo())
                                        <form
                                            method="POST"
                                            action="{{ route('monitoring-groups.publish-status-page', $monitoringGroup) }}"
                                        >
                                            @csrf
                                            <x-secondary-button
                                                :icon-only="true"
                                                title="{{ __('monitoring_group.actions.publish_status_page') }}"
                                                aria-label="{{ __('monitoring_group.actions.publish_status_page') }}"
                                            >
                                                <x-icon name="globe" class="h-4 w-4" />
                                            </x-secondary-button>
                                        </form>
                                        <x-secondary-button
                                            :href="route('monitoring-groups.edit', $monitoringGroup)"
                                            data-form-modal-trigger
                                            data-form-modal-name="monitoring-group-form-modal"
                                            :icon-only="true"
                                            title="{{ __('button.edit') }}"
                                            aria-label="{{ __('button.edit') }}"
                                        >
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </x-secondary-button>
                                        <form
                                            method="POST"
                                            action="{{ route('monitoring-groups.destroy', $monitoringGroup) }}"
                                            data-confirm-message="{{ __('monitoring_group.actions.delete.confirmation') }}"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button
                                                :icon-only="true"
                                                title="{{ __('button.delete') }}"
                                                aria-label="{{ __('button.delete') }}"
                                            >
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </x-danger-button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </x-container>
                    @endforeach
                </div>

                <div class="mt-4">{{ $monitoringGroups->links() }}</div>
            @endif

            <x-form-modal
                name="monitoring-group-form-modal"
                title="{{ __('monitoring_group.title') }}"
                description="{{ __('monitoring_group.form.monitorings') }}"
                max-width="3xl"
                :show="in_array($modalForm, ['monitoring-group-create', 'monitoring-group-edit'], true)"
            >
                <div class="p-6" x-ref="content">
                    @if ($modalForm === 'monitoring-group-create')
                        @include('monitoring-groups._modal-form', [
                            'action' => route('monitoring-groups.store'),
                            'monitorings' => $modalMonitorings,
                            'modal' => true,
                        ])
                    @elseif ($modalForm === 'monitoring-group-edit' && $modalMonitoringGroup)
                        @include('monitoring-groups._modal-form', [
                            'action' => route('monitoring-groups.update', $modalMonitoringGroup),
                            'monitoringGroup' => $modalMonitoringGroup,
                            'monitorings' => $modalMonitorings,
                            'modal' => true,
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
