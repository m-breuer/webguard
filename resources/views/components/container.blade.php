@props([
    'class' => '',
    'id' => '',
    'space' => false,
])

@php
    $classes = 'w-full px-4 sm:px-6 lg:px-8';
    $classes .= ' ' . $class;
    $classes .= ' bg-white shadow-md rounded-lg dark:bg-gray-800';
    $classes .= ' p-6';
    $classes .= ' text-gray-900 dark:text-gray-100';

    if ($space === 'true' || $space === true) {
        $classes .= ' mb-4';
    }
@endphp

<div {{ $attributes->class($classes)->merge(['id' => $id]) }}>
    {{ $slot }}
</div>
