<x-container space="true">
    <x-heading type="h2">{{ __('profile.information.heading') }}</x-heading>
    <x-paragraph>
        {{ __('profile.information.description') }}
    </x-paragraph>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('profile.fields.name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus
                autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('profile.fields.email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <x-paragraph>
                        {{ __('profile.information.email_unverified') }}

                        <button form="send-verification"
                            class="focus:outline-hidden rounded-md text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                            {{ __('profile.information.send_verification_email') }}
                        </button>
                    </x-paragraph>
                </div>
            @else
                <div>
                    <x-paragraph class="text-green-600">
                        {{ __('profile.messages.email_verified') }}
                    </x-paragraph>
                </div>
            @endif
        </div>

        <div class="w-full md:w-1/2" x-data="{ theme: '{{ old('theme', $user->theme) }}' }">
            <x-input-label for="theme" :value="__('profile.fields.theme')" />
            <x-select-input id="theme" class="block w-full" name="theme"
                @change="theme = $event.target.value">
                <option value="light" :selected="theme === 'light'">{{ __('profile.fields.theme_light') }}
                </option>
                <option value="dark" :selected="theme === 'dark'">{{ __('profile.fields.theme_dark') }}
                </option>
                <option value="system" :selected="theme === 'system'">{{ __('profile.fields.theme_system') }}
                </option>
            </x-select-input>
            <x-input-error :messages="$errors->get('theme')" />
        </div>

        <x-primary-button>{{ __('button.update') }}</x-primary-button>
    </form>
</x-container>
