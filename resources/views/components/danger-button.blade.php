@props(['iconOnly' => false])

@php
    $class = $iconOnly
        ? 'inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-transparent bg-red-600 font-semibold text-white transition ease-in-out duration-150 hover:bg-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600'
        : 'inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-red-700 dark:hover:bg-red-600';
@endphp

<button
    {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>
    {{ $slot }}
</button>
