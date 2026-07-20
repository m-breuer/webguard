@props([
    'href',
    'iconClass',
    'actionLabel' => null,
    'statusClass' => 'text-gray-500 dark:text-gray-400',
    'id' => null,
])

<a href="{{ $href }}" {{ $attributes->merge(['id' => $id])->class('group flex items-center gap-4 px-5 py-4 transition hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-purple-500 dark:hover:bg-gray-900 sm:px-6') }}>
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $iconClass }}" aria-hidden="true">
        {{ $icon }}
    </span>
    <span class="min-w-0 flex-1">
        <span class="block truncate font-semibold text-gray-900 group-hover:text-purple-700 dark:text-gray-100 dark:group-hover:text-purple-300">{{ $title }}</span>
        @isset($context)
            <span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">{{ $context }}</span>
        @endisset
    </span>
    @if ($actionLabel)
        <span class="hidden shrink-0 text-xs font-semibold text-purple-700 sm:block dark:text-purple-300">{{ $actionLabel }}</span>
    @endif
    @isset($status)
        <span class="shrink-0 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
    @endisset
    <x-dashboard.chevron class="h-5 w-5 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-purple-600" />
</a>
