@props([
    'class' => '',
    'bold' => false,
    'space' => false,
])

@php
    $classes = $class;
    $classes .= ' text-base dark:text-gray-200';

    if ($bold === 'true' || $bold === true) {
        $classes .= ' font-bold';
    } else {
        $classes .= ' font-normal';
    }

    if ($space === 'true' || $space === true) {
        $classes .= ' mb-2';
    }
@endphp

<p {{ $attributes->class($classes) }}>
    {{ $slot }}
</p>
