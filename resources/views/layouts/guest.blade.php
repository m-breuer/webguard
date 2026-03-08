<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ session('theme', 'system') === 'dark' ? 'dark' : '' }}" data-theme="{{ session('theme', 'system') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! $head ?? '' !!}

    <title>{{ __('app.title') }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    <meta name="description" content="{{ __('app.description') }}">
    <meta name="keywords" content="{{ __('app.keywords') }}">
    <meta name="author" content="{{ __('app.author') }}">
    <meta property="og:title" content="{{ __('app.og_title') }}">
    <meta property="og:description" content="{{ __('app.og_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body class="font-sans text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
    <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-900">
        <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <a href="{{ route('welcome') }}" class="flex items-center">
                        <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="h-8 w-8">
                        <x-span class="ms-2 text-xl font-bold text-gray-800 dark:text-gray-100">
                            {{ __('app.name') }}
                        </x-span>
                    </a>
                    <x-language-switch id="language-switch-guest" />
                </div>
            </div>
        </nav>

        <div class="flex flex-1 items-center justify-center p-3">
            <main class="w-full overflow-hidden bg-white p-6 shadow-md dark:bg-gray-800 sm:max-w-xl sm:rounded-lg">
                {{ $slot }}
            </main>
        </div>

        @include('components.footer')
    </div>

    @stack('scripts')
</body>

</html>
