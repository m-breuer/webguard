@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-purple-400 text-start text-base font-medium text-purple-700 bg-purple-50 focus:outline-hidden focus:text-purple-800 focus:bg-purple-100 focus:border-purple-700 transition duration-150 ease-in-out dark:text-purple-300 dark:bg-gray-700'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-hidden focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
