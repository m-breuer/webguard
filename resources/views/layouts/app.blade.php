<!DOCTYPE html>
@php
    $theme = auth()->check() ? auth()->user()->theme : 'system';
    if (! in_array($theme, ['light', 'dark', 'system'], true)) {
        $theme = 'system';
    }
@endphp
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="{{ $theme === 'dark' ? 'dark' : '' }}"
    data-theme="{{ $theme }}"
    data-confirm-title="{{ __('app.confirmation.title') }}"
    data-confirm-confirm="{{ __('app.confirmation.confirm') }}"
    data-confirm-cancel="{{ __('button.cancel') }}"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {!! $head ?? '' !!}

    <title>{{ __('app.title') }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png" />
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png" />

    <x-theme-bootstrap />
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <script>
        window.App = {
            locale: '{{ app()->getLocale() }}',
        };
    </script>
</head>

<body
    x-data="{
        mobileOpen: false,
        sidebarCollapsed: window.localStorage.getItem('webguard.sidebar.collapsed') === 'true',
    }"
    x-init="
        $watch('sidebarCollapsed', (value) =>
            window.localStorage.setItem('webguard.sidebar.collapsed', value ? 'true' : 'false'),
        )
    "
    class="flex min-h-screen flex-col justify-start bg-slate-50 font-sans antialiased dark:bg-slate-950 dark:text-gray-100"
>
    @include('layouts.navigation')

    <div class="min-h-screen" :class="{ 'lg:ps-20': sidebarCollapsed, 'lg:ps-64': ! sidebarCollapsed }">
        @isset($header)
            <header class="border-b border-purple-100/80 bg-white/95 shadow-sm dark:border-purple-900/50 dark:bg-slate-900/95">
                <x-main>
                    <x-flex class="py-6 sm:items-center sm:justify-between"> {{ $header }} </x-flex>
                </x-main>
            </header>
        @endisset

        <main class="py-6">
            {{ $slot }}

            @include('components.toast')
            <x-confirm-dialog />
        </main>

        @include('components.footer')
    </div>

    @stack('scripts')
</body>
</html>
