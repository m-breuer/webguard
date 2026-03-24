<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ session('theme', 'system') === 'dark' ? 'dark' : '' }}" data-theme="{{ session('theme', 'system') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="{{ isset($robots) ? trim($robots) : 'index, follow' }}">

    {!! $head ?? '' !!}

    <title>{{ isset($title) ? trim($title) : __('app.title') }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    <meta name="description" content="{{ isset($description) ? trim($description) : __('app.description') }}">
    <meta name="keywords" content="{{ isset($keywords) ? trim($keywords) : __('app.keywords') }}">
    <meta name="author" content="{{ __('app.author') }}">

    <meta property="og:title" content="{{ isset($ogTitle) ? trim($ogTitle) : __('app.og_title') }}">
    <meta property="og:description" content="{{ isset($ogDescription) ? trim($ogDescription) : __('app.og_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ isset($ogUrl) ? trim($ogUrl) : url('/') }}">
    <meta property="og:image" content="{{ isset($ogImage) ? trim($ogImage) : Vite::asset('resources/images/Logo-WebGuard.png') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ isset($twitterTitle) ? trim($twitterTitle) : (isset($ogTitle) ? trim($ogTitle) : __('app.og_title')) }}">
    <meta name="twitter:description" content="{{ isset($twitterDescription) ? trim($twitterDescription) : (isset($ogDescription) ? trim($ogDescription) : __('app.og_description')) }}">
    <meta name="twitter:image" content="{{ isset($twitterImage) ? trim($twitterImage) : (isset($ogImage) ? trim($ogImage) : Vite::asset('resources/images/Logo-WebGuard.png')) }}">

    <link rel="canonical" href="{{ isset($canonical) ? trim($canonical) : url('/') }}">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body class="bg-slate-950 font-sans antialiased text-slate-100">
    <div class="relative min-h-screen overflow-x-hidden bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[420px] bg-[radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.2),transparent_48%),radial-gradient(circle_at_80%_10%,rgba(56,189,248,0.2),transparent_42%)]"></div>

        <header class="border-b border-slate-800/70 bg-slate-950/80 backdrop-blur-sm">
            <nav aria-label="{{ __('welcome.nav.aria') }}">
                <x-main class="flex w-full items-center justify-between py-4">
                    <a href="{{ route('welcome') }}" class="flex items-center gap-3">
                        <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="{{ __('welcome.nav.logo_alt') }}" class="h-9 w-9">
                        <x-span class="text-lg font-bold tracking-tight text-white">{{ __('app.name') }}</x-span>
                    </a>

                    <div class="hidden items-center gap-8 md:flex">
                        <a href="#features" class="text-sm font-medium text-slate-200 transition hover:text-emerald-300">{{ __('welcome.nav.features') }}</a>
                        <a href="#proof" class="text-sm font-medium text-slate-200 transition hover:text-emerald-300">{{ __('welcome.nav.proof') }}</a>
                        <a href="#pricing-cta" class="text-sm font-medium text-slate-200 transition hover:text-emerald-300">{{ __('welcome.nav.get_started') }}</a>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <x-language-switch id="language-switch-guest" variant="marketing" />

                        @guest
                            <x-secondary-button :href="route('login')"
                                class="hidden border-slate-700 bg-slate-950 text-slate-100 normal-case tracking-normal hover:border-slate-500 hover:bg-slate-800 sm:inline-flex">
                                {{ __('welcome.nav.login') }}
                            </x-secondary-button>
                            <x-primary-button :href="route('register')"
                                class="bg-emerald-400 text-slate-950 normal-case tracking-normal hover:bg-emerald-300 focus:ring-emerald-300">
                                {{ __('welcome.nav.signup') }}
                            </x-primary-button>
                        @else
                            <x-primary-button :href="route('dashboard')"
                                class="bg-emerald-400 text-slate-950 normal-case tracking-normal hover:bg-emerald-300 focus:ring-emerald-300">
                                {{ __('welcome.nav.dashboard') }}
                            </x-primary-button>
                        @endguest
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
