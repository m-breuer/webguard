<x-guest-layout>
    <div x-data="guestLogin" x-init="init()">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="text-center">
            <x-heading class="flex items-center justify-center">
                <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
                {{ __('auth.login.title') }}
            </x-heading>
            <x-paragraph>
                {{ __('auth.login.description') }}
            </x-paragraph>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-input-label for="email" :value="__('auth.login.email')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus
                    autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="__('auth.login.password')" />

                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="mt-4 block">
                <x-text-checkbox id="remember_me" name="remember" label="{{ __('auth.login.remember') }}" />
            </div>

            <div class="mt-4 flex items-center">
                @if (Route::has('password.request'))
                    <a class="focus:outline-hidden rounded-md text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                        href="{{ route('password.request') }}">
                        {{ __('auth.login.forgot_password') }}
                    </a>
                @endif

                <x-primary-button>
                    {{ __('auth.login.button') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
