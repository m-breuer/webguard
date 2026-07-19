@php
    $activeTab = match (true) {
        request()->routeIs('monitoring-groups.*') => 'groups',
        request()->routeIs('status-pages.*') => 'status_pages',
        request()->routeIs('incidents.analytics') => 'analytics',
        default => 'overview',
    };

    $tabs = [
        [
            'key' => 'overview',
            'label' => __('incidents.analytics.overview.tabs.overview'),
            'href' => route('monitorings.index'),
        ],
        [
            'key' => 'groups',
            'label' => __('incidents.analytics.overview.tabs.groups'),
            'href' => route('monitoring-groups.index'),
        ],
        [
            'key' => 'status_pages',
            'label' => __('incidents.analytics.overview.tabs.status_pages'),
            'href' => route('status-pages.index'),
        ],
        [
            'key' => 'analytics',
            'label' => __('incidents.analytics.overview.tabs.analytics'),
            'href' => route('incidents.analytics'),
        ],
    ];
@endphp

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

    <nav class="mt-6 -mb-6 overflow-x-auto" aria-label="{{ __('incidents.analytics.title') }}">
        <div class="flex min-w-max items-center gap-1 border-b border-gray-200 dark:border-gray-700">
            @foreach ($tabs as $tab)
                @php($isActive = $activeTab === $tab['key'])
                <a href="{{ $tab['href'] }}"
                    @class([
                        'border-b-2 px-3 py-3 text-sm transition focus:outline-hidden focus:ring-2 focus:ring-purple-400',
                        'border-purple-500 font-semibold text-purple-700 dark:text-purple-300' => $isActive,
                        'border-transparent font-medium text-gray-500 hover:border-purple-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</div>
