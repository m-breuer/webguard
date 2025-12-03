@props([
    'class' => '',
    'id' => '',
])

@php
    $classes = 'mx-auto max-w-7xl';
    $classes .= ' ' . $class;
    $classes .= ' px-4 sm:px-6 lg:px-8';
    $classes .= '';
@endphp

<div {{ $attributes->class($classes)->merge(['id' => $id]) }}>
    {{ $slot }}
</div>
