@props(['disabled' => false, 'multiple' => false])

@php
    $selectClasses = $multiple
        ? 'border-gray-300 bg-white focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
        : 'appearance-none border-purple-200 bg-purple-50/40 pr-10 text-gray-900 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-xs dark:border-purple-800 dark:bg-purple-950/30 dark:text-gray-100';
@endphp

<div class="relative">
    <select @disabled($disabled) @if ($multiple) multiple @endif data-select-control="native"
        {{ $attributes->class($selectClasses) }}>
        {{ $slot }}
    </select>
    @unless ($multiple)
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-purple-600 dark:text-purple-300"
            aria-hidden="true">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-.02-1.06z"
                    clip-rule="evenodd" />
            </svg>
        </span>
    @endunless
</div>
