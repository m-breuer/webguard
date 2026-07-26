@props(['disabled' => false, 'multiple' => false])

@php
    $selectClasses = $multiple
        ? 'border-gray-300 bg-white focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
        : 'border-purple-200 bg-purple-50/40 text-gray-900 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-xs dark:border-purple-800 dark:bg-purple-950/30 dark:text-gray-100';
@endphp

<select @disabled($disabled) @if ($multiple) multiple @endif data-select-control="native"
    {{ $attributes->class($selectClasses) }}>
    {{ $slot }}
</select>
