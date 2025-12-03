<x-guest-layout>
    <div class="my-6">
        <x-heading type="h1" class="flex items-center justify-center text-center">
            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">

            {{ __('auth.confirm_password.title') }}
        </x-heading>
        <x-paragraph class="mt-2 text-center">
            {{ __('auth.confirm_password.description') }}
        </x-paragraph>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('auth.fields.password')" />

            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mt-4 flex justify-end">
            <x-primary-button>
                {{ __('button.save') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
