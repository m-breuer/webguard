<x-guest-layout>
    <div class="my-6">
        <x-heading type="h1" class="flex items-center justify-center text-center">
            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">

            {{ __('auth.reset_password.title') }}
        </x-heading>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('auth.reset_password.email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.reset_password.password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth.reset_password.confirm_password')" />

            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <x-primary-button>
                {{ __('auth.reset_password.button') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
