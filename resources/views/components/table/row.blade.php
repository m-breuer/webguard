@props(['class' => ''])
@php
    $classes = 'text-left  font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider px-6 py-3 ' . $class;
    if ($class === 'bg-gray-50') {
        $classes = 'bg-gray-50 ' . $classes;
    }
@endphp

<tr class="hover:bg-gray-200 dark:hover:bg-gray-700" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</tr>
