<x-container space="true">
    <x-heading type="h2">{{ __('profile.update_password.heading') }}</x-heading>
    <x-paragraph>
        {{ __('profile.update_password.description') }}
    </x-paragraph>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('profile.form.current_password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password"
                autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('profile.form.new_password')" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('profile.form.confirm_new_password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('button.update') }}</x-primary-button>
        </div>
    </form>
</x-container>
