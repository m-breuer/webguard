@props([
    'type' => 'h1',
    'space' => false,
])

@php
    $tag = match ($type) {
        'h1' => 'h1',
        'h2' => 'h2',
        'h3' => 'h3',
        'h4' => 'h4',
        'h5' => 'h5',
        default => 'h6',
    };
    $classes = match ($type) {
        'h1' => 'text-2xl sm:text-3xl font-bold',
        'h2' => 'text-xl sm:text-2xl font-semibold',
        'h3' => 'text-lg sm:text-xl font-medium',
        'h4' => 'text-lg font-medium',
        'h5' => 'text-base font-medium',
        default => ' font-medium',
    };

    if ($space === 'true' || $space === true) {
        $classes .= ' mb-2';
    }

    $classes .= ' text-gray-900 dark:text-gray-100';
@endphp

<{{ $tag }} {{ $attributes->class($classes) }}>
    {{ $slot }}
    </{{ $tag }}>
