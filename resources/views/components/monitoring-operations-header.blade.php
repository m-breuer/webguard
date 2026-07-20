<div class="w-full" data-monitoring-context>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <x-heading type="h1">{{ __('incidents.analytics.title') }}</x-heading>
            <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                {{ __('incidents.analytics.description') }}
            </p>
        </div>

        @isset($actions)
            <div class="shrink-0 sm:ml-auto">
                {{ $actions }}
            </div>
        @endisset
    </div>

</div>
