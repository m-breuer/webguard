@php
    $theme = auth()->check() ? auth()->user()->theme : 'system';
    $homeUrl = auth()->check() ? route('dashboard') : route('login');
    $homeLabel = auth()->check() ? __('errors.actions.dashboard') : __('errors.actions.login');

    if (! in_array($theme, ['light', 'dark', 'system'], true)) {
        $theme = 'system';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme === 'dark' ? 'dark' : '' }}" data-theme="{{ $theme }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ __('errors.meta_title', ['status' => $status, 'app' => __('app.name')]) }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    <x-theme-bootstrap />
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="relative flex min-h-screen flex-col overflow-hidden">
        <div class="pointer-events-none absolute -left-32 -top-32 h-80 w-80 rounded-full bg-purple-200/30 blur-3xl dark:bg-purple-950/30" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 h-96 w-96 rounded-full bg-emerald-100/50 blur-3xl dark:bg-emerald-950/20" aria-hidden="true"></div>

        <header class="relative border-b border-purple-100/80 bg-white/90 dark:border-purple-900/50 dark:bg-slate-900/90">
            <x-main class="flex h-16 items-center justify-between">
                <a href="{{ $homeUrl }}" class="flex items-center gap-3" aria-label="{{ __('app.name') }}">
                    <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="{{ __('app.logo_alt') }}" class="h-9 w-9">
                    <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('app.name') }}</span>
                </a>

                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.12)]" aria-hidden="true"></span>
                    {{ __('errors.eyebrow') }}
                </span>
            </x-main>
        </header>

        <main class="relative flex flex-1 items-center px-4 py-12 sm:px-6 lg:px-8">
            <x-main class="w-full">
                <section class="mx-auto grid max-w-5xl overflow-hidden rounded-2xl border border-purple-100 bg-white shadow-[0_24px_70px_-35px_rgba(91,33,182,0.45)] dark:border-purple-900/60 dark:bg-slate-900 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="relative flex min-h-64 items-center justify-center overflow-hidden border-b border-purple-100 bg-gradient-to-br from-purple-700 via-purple-600 to-indigo-700 p-8 text-white dark:border-purple-900/60 lg:min-h-[28rem] lg:border-b-0 lg:border-e">
                        <div class="absolute inset-0 opacity-20" aria-hidden="true">
                            <div class="absolute -left-12 top-8 h-48 w-48 rounded-full border border-white/60"></div>
                            <div class="absolute -left-20 top-0 h-64 w-64 rounded-full border border-white/40"></div>
                            <div class="absolute -bottom-20 -right-16 h-64 w-64 rounded-full border border-white/30"></div>
                            <div class="absolute bottom-8 right-8 h-3 w-3 rounded-full bg-emerald-300"></div>
                            <div class="absolute right-24 top-16 h-2 w-2 rounded-full bg-white"></div>
                        </div>

                        <div class="relative text-center">
                            <span class="text-xs font-bold uppercase tracking-[0.3em] text-purple-200">{{ __('errors.status_label') }}</span>
                            <p class="mt-2 text-8xl font-extrabold leading-none tracking-[-0.08em] sm:text-9xl">{{ $status }}</p>
                            <div class="mx-auto mt-6 flex items-center justify-center gap-2 text-sm font-medium text-purple-100">
                                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                                <span>{{ __('errors.eyebrow') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center p-8 sm:p-12 lg:p-14">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-purple-600 dark:text-purple-300">{{ __('errors.eyebrow') }}</p>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">{{ $error['title'] }}</h1>
                        <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">{{ $error['message'] }}</p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <x-primary-button :href="$homeUrl" class="gap-2">
                                <x-icon name="home" class="h-4 w-4" />
                                {{ $homeLabel }}
                            </x-primary-button>
                        </div>

                        <p class="mt-8 border-t border-slate-200 pt-5 text-sm leading-6 text-slate-500 dark:border-slate-700 dark:text-slate-400">{{ __('errors.return_hint') }}</p>
                    </div>
                </section>
            </x-main>
        </main>

        @include('components.footer')
    </div>
</body>

</html>
