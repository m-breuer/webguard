<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('status_page.title') }}</x-heading>

        @if (!Auth::user()->isDemo())
            <x-primary-button :href="route('status-pages.create')" class="sm:ml-auto"
                data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                {{ __('button.create') }}
            </x-primary-button>
        @endif
    </x-slot>

    <x-main>
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
        @if ($statusPages->isEmpty())
            <x-container class="text-center">
                <x-heading type="h2">{{ __('status_page.empty.title') }}</x-heading>
                <x-paragraph space="true">{{ __('status_page.empty.text') }}</x-paragraph>
                @if (!Auth::user()->isDemo())
                    <x-primary-button :href="route('status-pages.create')" data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                        {{ __('button.create') }}
                    </x-primary-button>
                @endif
            </x-container>
        @else
            <div class="space-y-4">
                @foreach ($statusPages as $statusPage)
                    <x-container>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <x-heading type="h2">{{ $statusPage->name }}</x-heading>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                                        {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                                    </x-badge>
                                    <span>{{ trans_choice('status_page.components_count', $statusPage->components_count, ['count' => $statusPage->components_count]) }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if ($statusPage->is_public)
                                    <x-secondary-button :href="route('public-status-pages.show', $statusPage)" target="_blank">
                                        {{ __('status_page.actions.public_page') }}
                                    </x-secondary-button>
                                @endif
                                <x-secondary-button :href="route('status-pages.show', $statusPage)">
                                    {{ __('button.show') }}
                                </x-secondary-button>
                                @if (!Auth::user()->isDemo())
                                    <x-secondary-button :href="route('status-pages.edit', $statusPage)"
                                        data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                                        {{ __('button.edit') }}
                                    </x-secondary-button>
                                @endif
                            </div>
                        </div>
                    </x-container>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $statusPages->links() }}
            </div>
        @endif

        <x-form-modal name="status-page-form-modal" title="{{ __('status_page.title') }}"
            description="{{ __('status_page.form.components') }}" max-width="5xl"
            :show="in_array($modalForm, ['status-page-create', 'status-page-edit'], true)">
            <div class="p-6" x-ref="content">
                @if ($modalForm === 'status-page-create')
                    @include('status-pages._form', [
                        'action' => route('status-pages.store'),
                        'submitLabel' => __('button.create'),
                        'monitorings' => $modalMonitorings,
                        'defaultComponents' => $modalDefaultComponents,
                        'modal' => true,
                    ])
                @elseif ($modalForm === 'status-page-edit' && $modalStatusPage)
                    @include('status-pages._form', [
                        'action' => route('status-pages.update', $modalStatusPage),
                        'method' => 'PATCH',
                        'submitLabel' => __('button.update'),
                        'statusPage' => $modalStatusPage,
                        'monitorings' => $modalMonitorings,
                        'defaultComponents' => [],
                        'modal' => true,
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
