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
    <script>
        window.App = {
            locale: '{{ app()->getLocale() }}'
        }
    </script>
</head>

<body class="bg-gray-100 font-sans antialiased dark:bg-gray-900 dark:text-gray-100">
    <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('welcome') }}" class="flex items-center">
                    <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="h-8 w-8">
                    <x-span class="ms-2 text-xl font-bold text-gray-800 dark:text-gray-100">
                        {{ __('app.name') }}
                    </x-span>
                </a>
                @guest
                    <x-language-switch id="language-switch-public" />
                @endguest
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

    <main class="py-6">
        {{ $slot }}
    </main>

    @include('components.footer')

    @stack('scripts')
</body>

</html>
