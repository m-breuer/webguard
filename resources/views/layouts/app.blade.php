<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ session('theme', 'system') === 'dark' ? 'dark' : '' }}" data-theme="{{ session('theme', 'system') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! $head ?? '' !!}

    <title>WebGuard – Website, Server & Port Monitoring (GDPR-ready)</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    <meta name="description"
        content="WebGuard is the GDPR-compliant monitoring solution for websites, servers, and ports. Track uptime, performance, and security from a single dashboard.">
    <meta name="keywords"
        content="uptime monitoring, server monitoring, port check, GDPR monitoring, WebGuard, website monitoring, performance tracker">
    <meta name="author" content="Marcel Breuer">
    <meta property="og:title" content="WebGuard – Website, Server & Port Monitoring (GDPR-ready)">
    <meta property="og:description"
        content="GDPR-compliant and powerful monitoring for your websites, servers, and ports. Uptime, response times, and security at a glance.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body
    class="flex min-h-screen flex-col justify-start bg-gray-100 font-sans antialiased dark:bg-gray-900 dark:text-gray-100">
    @include('layouts.navigation')

    @isset($header)
        <header class="bg-white shadow-sm dark:bg-gray-700">
            <x-main>
                <x-flex class="py-6 sm:items-center sm:justify-between">
                    {{ $header }}
                </x-flex>
            </x-main>
        </header>
    @endisset

    <main class="py-6">
        {{ $slot }}

        @include('components.toast')
    </main>

    @include('components.footer')

    @stack('scripts')
</body>

</html>
