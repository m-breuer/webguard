<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-heading type="h1">{{ __('dashboard.greeting', ['name' => Auth::user()->name]) }}</x-heading>
                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.description') }}</p>
            </div>
        </div>
    </x-slot>

    <x-main>
        <div
            id="dashboard-page-content"
            data-dashboard-loader
            data-endpoint="{{ route('dashboard', request()->query(), absolute: false) }}"
            x-data="dashboardLoader()"
            class="space-y-6"
            aria-live="polite"
        >
            <x-container>
                <div data-dashboard-loading>
                    <x-loading-skeleton variant="dashboard" />
                </div>
                <p data-dashboard-error class="text-sm font-semibold text-red-600 dark:text-red-300" hidden>
                    {{ __('search.messages.error') }}
                </p>
            </x-container>
        </div>

        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            <x-form-modal name="monitoring-form-modal" title="{{ __('monitoring.title') }}"
                description="{{ __('monitoring.form.sections.basic') }}" max-width="6xl">
                <div class="p-6" x-ref="content">
                    <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
