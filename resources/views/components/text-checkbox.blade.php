@props([
    'name',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'label' => '',
    'disabled' => false,
    'required' => false,
])

@php
    $id = $id ?? $name;
@endphp

<label for="{{ $id }}" {{ $attributes->class(['inline-flex items-center']) }}>
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}"
        class="shadow-xs focus:ring-3 rounded-sm border-gray-300 text-purple-600 focus:border-purple-300 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
        @checked($checked) @disabled($disabled) @required($required)>
    <x-span class="ms-2 text-gray-600 dark:text-gray-300">{{ $label }}</x-span>
</label>
