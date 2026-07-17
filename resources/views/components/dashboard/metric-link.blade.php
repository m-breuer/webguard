@props([
    'href',
    'value',
    'label',
    'tone',
])

<a href="{{ $href }}" {{ $attributes->class('group flex items-center gap-2 px-2 py-3 first:pl-0 last:pr-0 sm:justify-center sm:first:pl-2 sm:last:pr-2') }}>
    <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $tone }}"></span>
    <span class="min-w-0">
        <span class="block text-xl font-bold leading-none text-gray-900 group-hover:text-purple-700 dark:text-gray-100 dark:group-hover:text-purple-300">{{ $value }}</span>
        <span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
    </span>
</a>
