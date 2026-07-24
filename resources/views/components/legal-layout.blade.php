<!DOCTYPE html>
@php
    $theme = 'system';
    if (! in_array($theme, ['light', 'dark', 'system'], true)) {
        $theme = 'system';
    }

    $pageTitle = isset($title) ? trim((string) $title) : __('app.title');
    $homeUrl = config('app.marketing_url') ?: route('login');
    $homeIsExternal = filled(config('app.marketing_url'));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme === 'dark' ? 'dark' : '' }}" data-theme="{{ $theme }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {!! $head ?? '' !!}

    <title>{{ $pageTitle }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body class="bg-slate-50 font-sans antialiased text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div
        class="relative min-h-screen overflow-x-hidden bg-gradient-to-b from-slate-50 via-white to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[420px] bg-[radial-gradient(circle_at_20%_20%,rgba(124,58,237,0.13),transparent_48%),radial-gradient(circle_at_80%_10%,rgba(99,102,241,0.12),transparent_42%)] dark:bg-[radial-gradient(circle_at_20%_20%,rgba(124,58,237,0.2),transparent_48%),radial-gradient(circle_at_80%_10%,rgba(99,102,241,0.18),transparent_42%)]">
        </div>

        <header class="relative z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-sm dark:border-slate-800/70 dark:bg-slate-950/80" style="z-index: 40;">
            <nav aria-label="{{ __('app.navigation.home') }}">
                <x-main class="flex w-full items-center justify-between py-4">
                    <a href="{{ $homeUrl }}" @if ($homeIsExternal) target="_blank" rel="noopener" @endif class="flex items-center gap-3">
                        <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="{{ __('app.logo_alt') }}" class="h-9 w-9">
                        <x-span class="hidden text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:inline">{{ __('app.name') }}</x-span>
                    </a>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <x-language-switch id="language-switch-guest" variant="legal" />

                        <x-primary-button :href="route('register')"
                            class="!border-purple-600 !bg-purple-600 !text-white !normal-case !tracking-normal hover:!bg-purple-700 focus:!ring-purple-500 dark:!border-purple-400 dark:!bg-purple-400 dark:!text-slate-950 dark:hover:!bg-purple-300 dark:focus:!ring-purple-300">
                            {{ __('auth.auth_switch.register') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login')"
                            class="!border-slate-300 !bg-white !text-slate-700 !normal-case !tracking-normal transition hover:!border-slate-400 hover:!bg-slate-100 dark:!border-slate-600 dark:!bg-slate-900/70 dark:!text-slate-100 dark:hover:!border-slate-500 dark:hover:!bg-slate-800">
                            {{ __('auth.auth_switch.login') }}
                        </x-secondary-button>
                    </div>
                </x-main>
            </nav>
        </header>

        {{ $slot }}

        @include('components.footer')
    </div>

    @stack('scripts')
</body>

</html>
