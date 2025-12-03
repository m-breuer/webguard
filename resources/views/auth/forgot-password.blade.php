<x-guest-layout>
    <div class="my-6">
        <x-heading type="h1" class="flex items-center justify-center text-center">
            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
            {{ __('auth.forgot_password.title') }}
        </x-heading>
        <x-paragraph class="mt-2 text-center">
            {{ __('auth.forgot_password.description') }}
        </x-paragraph>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('auth.login.email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <x-primary-button>
                {{ __('auth.forgot_password.button') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
