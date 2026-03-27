@php
    $requestedMode = null;

    if (old('form_mode')) {
        $requestedMode = old('form_mode');
    } elseif (request()->boolean('guest')) {
        $requestedMode = 'demo';
    } elseif (in_array(request()->query('mode'), ['login', 'register', 'demo'], true)) {
        $requestedMode = (string) request()->query('mode');
    } elseif (($authMode ?? null) === 'register') {
        $requestedMode = 'register';
    } else {
        $requestedMode = 'login';
    }

    $initialMode = in_array($requestedMode, ['login', 'register', 'demo'], true) ? $requestedMode : 'login';
@endphp

<x-guest-layout card-width="sm:max-w-7xl">
    <div x-data="guestLogin(@js($initialMode))" x-init="init()" data-initial-mode="{{ $initialMode }}">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="grid gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
            <aside class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                <x-heading type="h3" class="mb-1">{{ __('auth.auth_switch.title') }}</x-heading>
                <x-paragraph class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('auth.auth_switch.description') }}
                </x-paragraph>

                <div class="mt-4 space-y-2">
                    <button type="button" @click="switchMode('login')"
                        :class="mode === 'login'
                            ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm font-semibold transition">
                        {{ __('auth.auth_switch.login') }}
                    </button>

                    <button type="button" @click="switchMode('register')"
                        :class="mode === 'register'
                            ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm font-semibold transition">
                        {{ __('auth.auth_switch.register') }}
                    </button>

                    <button type="button" @click="switchMode('demo')"
                        :class="mode === 'demo'
                            ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm font-semibold transition">
                        {{ __('auth.auth_switch.demo') }}
                    </button>
                </div>
            </aside>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800">
                <div x-show="mode === 'login' || mode === 'demo'">
                    <div class="text-center">
                        <x-heading class="flex items-center justify-center">
                            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
                            {{ __('auth.login.title') }}
                        </x-heading>
                        <x-paragraph>
                            {{ __('auth.login.description') }}
                        </x-paragraph>
                        <x-paragraph x-show="mode === 'demo'" class="mt-2 text-sm text-purple-600 dark:text-purple-300">
                            {{ __('auth.login.demo_hint') }}
                        </x-paragraph>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="form_mode" :value="mode">

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

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <x-primary-button>
                                {{ __('auth.login.button') }}
                            </x-primary-button>

                            @if (Route::has('password.request'))
                                <a class="focus:outline-hidden rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:text-gray-300 dark:hover:text-gray-100"
                                    href="{{ route('password.request') }}">
                                    {{ __('auth.login.forgot_password') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div x-show="mode === 'register'">
                    <div class="text-center">
                        <x-heading class="flex items-center justify-center">
                            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
                            {{ __('auth.register.title') }}
                        </x-heading>
                        <x-paragraph>
                            {{ __('auth.register.description') }}
                        </x-paragraph>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="form_mode" value="register">

                        <div>
                            <x-input-label for="register_name" :value="__('auth.register.name')" />
                            <x-text-input id="register_name" type="text" name="name" :value="old('name')" required autofocus
                                autocomplete="name" />
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="register_email" :value="__('auth.register.email')" />
                            <x-text-input id="register_email" type="email" name="email" :value="old('email')" required
                                autocomplete="username" />
                            <x-input-error :messages="$errors->get('email')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="register_password" :value="__('auth.register.password')" />

                            <x-text-input id="register_password" type="password" name="password" required autocomplete="new-password" />

                            <x-input-error :messages="$errors->get('password')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="register_password_confirmation" :value="__('auth.register.confirm_password')" />

                            <x-text-input id="register_password_confirmation" type="password" name="password_confirmation" required
                                autocomplete="new-password" />

                            <x-input-error :messages="$errors->get('password_confirmation')" />
                        </div>

                        <div class="mt-4 space-y-3">
                            <label for="register_terms" class="inline-flex items-start">
                                <input id="register_terms" name="terms" type="checkbox" value="1"
                                    class="mt-0.5 rounded-sm border-gray-300 text-purple-600 shadow-xs focus:border-purple-300 focus:ring-3 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
                                    @checked(old('terms')) required>
                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">
                                    {!! __('auth.register.terms_agreement', ['terms_link' => route('terms-of-use'), 'privacy_link' => route('gdpr')]) !!}
                                </span>
                            </label>
                            <x-input-error :messages="$errors->get('terms')" />
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <x-primary-button>
                                {{ __('auth.register.button') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="mt-6 text-center">
                    <div class="my-4 flex items-center">
                        <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                        <span class="mx-4 flex-shrink px-2 text-gray-500 dark:text-gray-400">{{ __('auth.or_continue_with') }}</span>
                        <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                    </div>

                    <x-input-error :messages="$errors->get('socialite_error')" class="mb-4" />

                    <x-secondary-button :href="route('github.redirect')">
                        {{ __('auth.github_login') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
