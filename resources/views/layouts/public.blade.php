<!DOCTYPE html>
@php
    $homeUrl = config('app.marketing_url') ?: route('login');
    $homeIsExternal = filled(config('app.marketing_url'));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-confirm-title="{{ __('app.confirmation.title') }}"
    data-confirm-confirm="{{ __('app.confirmation.confirm') }}" data-confirm-cancel="{{ __('button.cancel') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! $head ?? '' !!}

    <title>{{ $title ?? __('app.public_status_title') }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <script>
        window.App = {
            locale: '{{ app()->getLocale() }}'
        }
    </script>
</head>

<body class="min-h-screen bg-slate-50 font-sans antialiased text-gray-900 dark:bg-slate-950 dark:text-gray-100">
    <nav class="border-b border-purple-100/80 bg-white/95 shadow-sm dark:border-purple-900/50 dark:bg-slate-900/95">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ $homeUrl }}" @if ($homeIsExternal) target="_blank" rel="noopener" @endif class="flex items-center gap-2.5 rounded-md focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="{{ __('app.logo_alt') }}" class="h-8 w-8">
                    <x-span class="text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {{ __('app.name') }}
                    </x-span>
                </a>
                <x-language-switch id="language-switch-public" />
            </div>
        </div>
    </nav>

    @isset($header)
        <header class="bg-white shadow-sm dark:bg-gray-700">
            <x-main>
                <x-flex class="py-6 sm:items-center sm:justify-between">
                    {{ $header }}
                </x-flex>
            </x-main>
        </header>
    @endisset

    <main class="py-8 sm:py-10">
        {{ $slot }}

        <x-confirm-dialog />
    </main>

    @include('components.footer')

    @stack('scripts')
</body>

</html>
