@props(['disabled' => false])

<input
    {{ $attributes->merge(['type' => 'checkbox', 'class' => 'rounded-sm border-gray-300 text-purple-600 shadow-xs focus:border-purple-300 focus:ring-3 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600']) }}>
