<x-container space="true">
    <x-heading type="h2">{{ __('profile.delete_account.heading') }}</x-heading>
    <x-paragraph space="true">
        {{ __('profile.delete_account.description') }}
    </x-paragraph>

    <x-danger-button x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">{{ __('button.delete') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <x-heading type="h2">{{ __('profile.delete_account.confirmation_question') }}</x-heading>

            <x-paragraph>
                {{ __('profile.delete_account.confirmation_warning') }}
            </x-paragraph>

            <x-input-label for="password" value="{{ __('profile.form.password') }}" class="sr-only" />

            <x-text-input id="password" name="password" type="password"
                placeholder="{{ __('profile.fields.password') }}" />

            <x-input-error :messages="$errors->userDeletion->get('password')" />

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('button.cancel') }}</x-secondary-button>

                <x-danger-button class="ms-3">{{ __('button.delete') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</x-container>
