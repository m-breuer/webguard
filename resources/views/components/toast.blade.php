@php
    $type = session()->has('success') ? 'success' : (session()->has('error') ? 'error' : null);
    $message = session($type);
@endphp

@if ($type && $message)
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 10000)" x-show="show" x-transition
        class="fixed bottom-4 right-4 z-50 max-w-sm rounded-sm px-4 py-3 shadow-lg"
        :class="{
            'bg-green-100 border border-green-400 text-green-700 dark:bg-green-800 dark:border-green-500 dark:text-green-200': '{{ $type }}'
            === 'success',
            'bg-red-100 border border-red-400 text-red-700 dark:bg-red-800 dark:border-red-500 dark:text-red-200': '{{ $type }}'
            === 'error'
        }"
        role="alert">
        <strong class="font-semibold">{{ $message }}</strong>
    </div>
@endif
