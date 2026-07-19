<x-app-layout>
    <x-slot name="header">
        <x-monitoring-operations-header>
            @if (!Auth::user()->isDemo())
                <x-slot name="actions">
                    <x-primary-button :href="route('status-pages.create')"
                        data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                        {{ __('button.create') }}
                    </x-primary-button>
                </x-slot>
            @endif
        </x-monitoring-operations-header>
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
            <div data-status-page-overview class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="hidden grid-cols-[minmax(0,1.6fr)_9rem_10rem_12rem] gap-4 border-b border-gray-200 bg-gray-50/80 px-5 py-3 text-xs font-bold uppercase tracking-[0.12em] text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400 md:grid">
                    <span>{{ __('status_page.table.name') }}</span>
                    <span>{{ __('status_page.table.access') }}</span>
                    <span>{{ __('status_page.table.components') }}</span>
                    <span class="text-right">{{ __('status_page.table.actions') }}</span>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($statusPages as $statusPage)
                        <div data-status-page-row class="grid gap-4 px-5 py-4 transition hover:bg-purple-50/40 dark:hover:bg-purple-950/20 md:grid-cols-[minmax(0,1.6fr)_9rem_10rem_12rem] md:items-center">
                            <div class="min-w-0">
                                <a href="{{ route('status-pages.show', $statusPage) }}" class="truncate text-base font-bold text-gray-900 hover:text-purple-700 dark:text-gray-100 dark:hover:text-purple-300">
                                    {{ $statusPage->name }}
                                </a>
                                @if ($statusPage->description)
                                    <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ $statusPage->description }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 md:hidden">
                                    <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                                        {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                                    </x-badge>
                                    <span>{{ trans_choice('status_page.components_count', $statusPage->components_count, ['count' => $statusPage->components_count]) }}</span>
                                </div>
                            </div>
                            <div class="hidden md:block">
                                <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                                    {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                                </x-badge>
                            </div>
                            <span class="hidden text-sm text-gray-600 dark:text-gray-300 md:block">
                                {{ trans_choice('status_page.components_count', $statusPage->components_count, ['count' => $statusPage->components_count]) }}
                            </span>
                            <div class="flex flex-wrap justify-start gap-2 md:justify-end">
                                <x-secondary-button :href="route('status-pages.show', $statusPage)">
                                    {{ __('button.show') }}
                                </x-secondary-button>
                                @if ($statusPage->is_public)
                                    <x-secondary-button :href="route('public-status-pages.show', $statusPage)" target="_blank">
                                        {{ __('status_page.actions.public_page') }}
                                    </x-secondary-button>
                                @endif
                                @if (!Auth::user()->isDemo())
                                    <x-secondary-button :href="route('status-pages.edit', $statusPage)"
                                        data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                                        {{ __('button.edit') }}
                                    </x-secondary-button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
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
