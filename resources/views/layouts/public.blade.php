<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! $head ?? '' !!}

    <title>{{ $title ?? __('app.public_status_title') }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" type="image/png">

    <meta property="og:title" content="{{ __('app.og_title') }}">
    <meta property="og:description" content="{{ __('app.og_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body class="bg-gray-100 font-sans antialiased dark:bg-gray-900 dark:text-gray-100">
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
    </main>

    @include('components.footer')

    @stack('scripts')
</body>

</html>
