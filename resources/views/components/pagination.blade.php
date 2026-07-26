@props([
    'paginator',
    'pageParam' => 'page',
    'async' => false,
])

@php
    $pagination = is_array($paginator)
        ? $paginator
        : [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'previous_url' => $paginator->previousPageUrl(),
            'next_url' => $paginator->nextPageUrl(),
        ];

    $previousUrl = $pagination['previous_url'] ?? request()->fullUrlWithQuery([$pageParam => $pagination['current_page'] - 1]);
    $nextUrl = $pagination['next_url'] ?? request()->fullUrlWithQuery([$pageParam => $pagination['current_page'] + 1]);
@endphp

@if ($pagination['last_page'] > 1)
    <div data-table-pagination {{ $attributes->merge(['class' => 'flex flex-col gap-3 text-sm text-gray-500 dark:text-gray-300 sm:flex-row sm:items-center sm:justify-between']) }}>
        <p>
            {{ __('search.table.showing') }} {{ $pagination['from'] ?? 0 }} {{ __('search.table.to') }} {{ $pagination['to'] ?? 0 }}
            {{ __('search.table.of') }} {{ $pagination['total'] }} {{ __('search.table.entries') }}
        </p>

        <nav class="flex items-center gap-1.5" aria-label="{{ __('search.table.pagination') }}">
            @if ($pagination['current_page'] > 1)
                <a href="{{ $previousUrl }}"
                    @if ($async) data-pagination-async @endif
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-700 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-purple-500 dark:hover:bg-purple-950/30 dark:hover:text-purple-200">
                    {{ __('pagination.previous') }}
                </a>
            @endif

            <span class="inline-flex items-center rounded-lg bg-purple-700 px-3 py-2 font-bold text-white dark:bg-purple-600">
                {{ $pagination['current_page'] }} / {{ $pagination['last_page'] }}
            </span>

            @if ($pagination['current_page'] < $pagination['last_page'])
                <a href="{{ $nextUrl }}"
                    @if ($async) data-pagination-async @endif
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-700 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-purple-500 dark:hover:bg-purple-950/30 dark:hover:text-purple-200">
                    {{ __('pagination.next') }}
                </a>
            @endif
        </nav>
    </div>
@endif
