@props([
    'href',
    'variant' => 'quiet',
    'label' => null,
    'modalName' => null,
])

@php
    $classes = match ($variant) {
        'solid' => 'inline-flex w-full items-center justify-between rounded-2xl bg-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
        'list' => 'group flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-white hover:text-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-purple-300',
        default => 'inline-flex items-center gap-2 text-sm font-semibold text-purple-700 transition hover:text-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:text-purple-300 dark:hover:text-purple-100',
    };
@endphp

@php
    $attributes = $attributes->class($classes);
    if ($modalName !== null) {
        $attributes = $attributes->merge([
            'data-form-modal-trigger' => true,
            'data-form-modal-name' => $modalName,
        ]);
    }
@endphp

<a href="{{ $href }}" {{ $attributes }}>
    <span>{{ $slot->isNotEmpty() ? $slot : $label }}</span>
    <x-dashboard.chevron class="h-4 w-4 shrink-0 {{ $variant === 'list' ? 'text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-purple-600' : '' }}" />
</a>
