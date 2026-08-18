<x-app-layout>
    <x-slot name="header">
        <x-monitoring-operations-header>
            @if (! Auth::user()->isDemo())
                <x-slot name="actions">
                    <x-primary-button
                        :href="route('status-pages.create')"
                        data-form-modal-trigger
                        data-form-modal-name="status-page-form-modal"
                    >
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
                    @if (! Auth::user()->isDemo())
                        <x-primary-button
                            :href="route('status-pages.create')"
                            data-form-modal-trigger
                            data-form-modal-name="status-page-form-modal"
                        >
                            {{ __('button.create') }}
                        </x-primary-button>
                    @endif
                </x-container>
            @else
                <div data-status-page-overview class="grid gap-4 xl:grid-cols-2">
                    @foreach ($statusPages as $statusPage)
                        <article
                            data-status-page-card
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-700"
                        >
                            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <a
                                            href="{{ route('status-pages.show', $statusPage) }}"
                                            class="truncate text-lg font-bold text-gray-900 hover:text-purple-700 dark:text-gray-100 dark:hover:text-purple-300"
                                        >
                                            {{ $statusPage->name }}
                                        </a>
                                        @if ($statusPage->is_public)
                                            <a
                                                href="{{ route('public-status-pages.show', $statusPage) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-purple-600 transition hover:bg-purple-100 hover:text-purple-800 focus:ring-2 focus:ring-purple-500 focus:outline-hidden dark:text-purple-300 dark:hover:bg-purple-950/40 dark:hover:text-purple-200"
                                                title="{{ __('status_page.detail.open_public_page') }}"
                                                aria-label="{{ __('status_page.detail.open_public_page') }}"
                                            >
                                                <x-icon name="external-link" class="h-4 w-4" />
                                            </a>
                                        @endif
                                    </div>
                                    @if ($statusPage->description)
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $statusPage->description }}
                                        </p>
                                    @endif
                                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                                            {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                                        </x-badge>
                                        <span>{{ trans_choice('status_page.components_count', $statusPage->components_count, ['count' => $statusPage->components_count]) }}</span>
                                    </div>
                                </div>

                                <div
                                    data-status-page-actions
                                    class="flex flex-nowrap justify-start gap-2 sm:justify-end"
                                >
                                    <x-secondary-button
                                        :href="route('status-pages.show', $statusPage)"
                                        :icon-only="true"
                                        title="{{ __('button.show') }}"
                                        aria-label="{{ __('button.show') }}"
                                    >
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </x-secondary-button>
                                    @if (! Auth::user()->isDemo())
                                        <x-secondary-button
                                            :href="route('status-pages.edit', $statusPage)"
                                            data-form-modal-trigger
                                            data-form-modal-name="status-page-form-modal"
                                            :icon-only="true"
                                            title="{{ __('button.edit') }}"
                                            aria-label="{{ __('button.edit') }}"
                                        >
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </x-secondary-button>
                                        <form
                                            method="POST"
                                            action="{{ route('status-pages.destroy', $statusPage) }}"
                                            data-confirm-message="{{ __('status_page.actions.delete_confirmation') }}"
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
                        </article>
                    @endforeach
                </div>

                <div class="mt-4">{{ $statusPages->links() }}</div>
            @endif

            <x-form-modal
                name="status-page-form-modal"
                title="{{ __('status_page.title') }}"
                description="{{ __('status_page.form.components') }}"
                max-width="5xl"
                :show="in_array($modalForm, ['status-page-create', 'status-page-edit'], true)"
            >
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
                        <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                        <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                        <div x-html="content"></div>
                    @endif
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
