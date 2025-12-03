@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-purple-500  font-medium leading-5 text-gray-900 focus:outline-hidden focus:border-purple-500 transition duration-150 ease-in-out dark:text-gray-100'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent  font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-purple-500 focus:outline-hidden focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-300 dark:hover:text-gray-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
