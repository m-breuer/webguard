@props([
    'type' => 'info',
    'class' => '',
])

@php
    $classes = 'inline-block rounded px-2 py-0.5 text-xs font-semibold ';
    $classes .= match ($type) {
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
    };
    $classes .= ' ' . $class;
@endphp

<span {{ $attributes->class($classes) }}>
    {{ $slot }}
</span>
