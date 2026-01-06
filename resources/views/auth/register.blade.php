<x-guest-layout>
    <div class="text-center">
        <x-heading class="flex items-center justify-center">
            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
            {{ __('auth.register.title') }}
        </x-heading>
        <x-paragraph>
            {{ __('auth.register.description') }}
        </x-paragraph>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('auth.register.name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus
                autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('auth.register.email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.register.password')" />

            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth.register.confirm_password')" />

            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        {{-- TODO: Terms Agreement --}}
        {{--
        <div class="mt-4">
            <label for="terms" class="flex items-center">
                <x-checkbox-input id="terms" name="terms" required />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                    {!! __('auth.register.terms_agreement', [
                        'terms_link' => route('terms.show'),
                        'policy_link' => route('policy.show'),
                    ]) !!}
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms')" />
        </div>
        --}}
        <input type="hidden" name="terms" value="1">

        <div class="mt-4 flex items-center justify-end">
            <a class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100"
                href="{{ route('login') }}">
                {{ __('auth.register.already_registered') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('auth.register.button') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <div class="my-4 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="mx-4 flex-shrink px-2 text-gray-500">{{ __('auth.or_continue_with') }}</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>

        <x-secondary-button :href="route('github.redirect')">
            {{ __('auth.github_login') }}
        </x-secondary-button>
    </div>
</x-guest-layout>
