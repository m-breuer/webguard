@props([
    'id',
    'endpoint',
    'paginator',
    'filters' => [],
    'searchPlaceholder' => __('search.fields.placeholder_generic'),
    'perPageOptions' => [5, 10, 25, 50],
    'initialSort' => '',
    'initialDirection' => 'asc',
    'initialSearch' => '',
    'initialFilters' => [],
    'class' => '',
])

@php
    $pagination = [
        'current_page' => $paginator->currentPage(),
        'last_page' => $paginator->lastPage(),
        'from' => $paginator->firstItem(),
        'to' => $paginator->lastItem(),
        'total' => $paginator->total(),
        'per_page' => $paginator->perPage(),
    ];

    $normalizedFilters = collect($filters)
        ->mapWithKeys(fn (array $filter): array => [(string) $filter['name'] => (string) ($initialFilters[$filter['name']] ?? '')])
        ->all();
@endphp

<div id="{{ $id }}"
    x-data="asyncTable({
        endpoint: @js($endpoint),
        search: @js($initialSearch),
        sort: @js($initialSort),
        direction: @js($initialDirection),
        perPage: @js($paginator->perPage()),
        filters: @js($normalizedFilters),
        pagination: @js($pagination),
        labels: {
            loading: @js(__('search.messages.loading')),
            error: @js(__('search.messages.error')),
        },
    })"
    {{ $attributes->merge(['class' => 'w-full text-left text-gray-500 dark:text-gray-300 ' . $class]) }}>
    @if (count($filters) > 0)
        <section class="mb-4 rounded-md bg-white p-4 shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('search.filter.heading') }}</h2>

            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($filters as $filter)
                    <label class="block">
                        <span class="sr-only">{{ $filter['label'] }}</span>
                        @if (($filter['type'] ?? 'select') === 'select')
                            <select
                                class="w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                x-model="filters['{{ $filter['name'] }}']"
                                @change="setFilter('{{ $filter['name'] }}', $event.target.value)">
                                <option value="">{{ $filter['placeholder'] ?? __('search.filter.all') }}</option>
                                @foreach ($filter['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="{{ $filter['type'] }}"
                                class="w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                placeholder="{{ $filter['placeholder'] ?? $filter['label'] }}"
                                x-model="filters['{{ $filter['name'] }}']"
                                @input.debounce.400ms="setFilter('{{ $filter['name'] }}', $event.target.value)"
                                @change="setFilter('{{ $filter['name'] }}', $event.target.value)" />
                        @endif
                    </label>
                @endforeach
            </div>
        </section>
    @endif

    <div
        class="mb-4 flex flex-col gap-3 rounded-md bg-white p-4 shadow-md dark:bg-gray-800 lg:flex-row lg:items-center lg:justify-between">
        <label class="w-24">
            <span class="sr-only">{{ __('search.table.per_page') }}</span>
            <select
                class="w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                x-model="perPage" @change="setPerPage($event.target.value)">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </label>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative block sm:w-72">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <span aria-hidden="true">&#128269;</span>
                </span>
                <input type="search"
                    class="w-full rounded-md border-gray-300 pl-9 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    placeholder="{{ $searchPlaceholder }}" x-model="search"
                    @input.debounce.400ms="setSearch($event.target.value)" />
            </label>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>

    <div class="relative overflow-hidden rounded-md bg-white shadow-md dark:bg-gray-800">
        <div x-show="loading" x-cloak
            class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 text-sm font-semibold text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
            {{ __('search.messages.loading') }}
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
                <tbody x-ref="body" class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    {{ $body }}
                </tbody>
            </table>
        </div>
    </div>

    <p x-show="error" x-text="error" class="mt-3 text-sm font-semibold text-red-600 dark:text-red-300"></p>

    <div class="mt-4 flex flex-col gap-3 text-sm text-gray-500 dark:text-gray-300 sm:flex-row sm:items-center sm:justify-between">
        <p>
            {{ __('search.table.showing') }}
            <span x-text="pagination.from ?? 0"></span>
            {{ __('search.table.to') }}
            <span x-text="pagination.to ?? 0"></span>
            {{ __('search.table.of') }}
            <span x-text="pagination.total"></span>
            {{ __('search.table.entries') }}
        </p>

        <nav class="flex flex-wrap items-center gap-1" aria-label="{{ __('search.table.pagination') }}">
            <button type="button"
                class="rounded-md bg-gray-100 px-3 py-2 font-semibold text-gray-700 transition hover:bg-purple-50 hover:text-purple-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                :disabled="pagination.current_page <= 1 || loading" @click="fetchPage(pagination.current_page - 1)">
                &laquo;
            </button>

            <template x-for="page in pages()" :key="`${page}-${pagination.current_page}`">
                <span>
                    <button x-show="page !== '...'" type="button"
                        class="rounded-md px-3 py-2 font-semibold transition"
                        :class="page === pagination.current_page
                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-100'
                            : 'bg-gray-100 text-gray-700 hover:bg-purple-50 hover:text-purple-700 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600'"
                        :disabled="loading" @click="fetchPage(page)" x-text="page"></button>
                    <span x-show="page === '...'" class="px-2 py-2">...</span>
                </span>
            </template>

            <button type="button"
                class="rounded-md bg-gray-100 px-3 py-2 font-semibold text-gray-700 transition hover:bg-purple-50 hover:text-purple-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                :disabled="pagination.current_page >= pagination.last_page || loading"
                @click="fetchPage(pagination.current_page + 1)">
                &raquo;
            </button>
        </nav>
    </div>
</div>
