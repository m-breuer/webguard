<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('profile.title') }}</x-heading>
    </x-slot>

    <x-main>
        <div class="space-y-6">
            @if ($fullPage)
                @include('profile.partials.update-profile-information-form')
                @include('profile.partials.update-password-form')
            @else
                <x-container>
                    <x-heading type="h2">{{ __('profile.information.heading') }}</x-heading>
                    <x-paragraph>{{ __('profile.information.description') }}</x-paragraph>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p class="font-semibold text-gray-950 dark:text-gray-50">{{ $user->name }}</p>
                            <p>{{ $user->email }}</p>
                            <p class="mt-2">{{ __('profile.notification_settings.heading') }}: {{ __('profile.notification_settings.description') }}</p>
                        </div>

                        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}"
                            class="flex flex-wrap gap-2">
                            <x-primary-button :href="route('profile.edit', ['modal' => 'profile-information'])"
                                data-form-modal-trigger data-form-modal-name="profile-information-form-modal"
                                data-form-modal-param="profile-information">
                                {{ __('profile.actions.update_profile') }}
                            </x-primary-button>

                            <x-form-modal name="profile-information-form-modal" title="{{ __('profile.information.title') }}"
                                description="{{ __('profile.notification_settings.description') }}" max-width="6xl"
                                :show="$modalForm === 'profile-information'">
                                <div class="p-6" x-ref="content">
                                    @if ($modalForm === 'profile-information')
                                        @include('profile.partials.update-profile-information-form', ['modal' => true])
                                    @else
                                        <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                                        <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                                        <div x-html="content"></div>
                                    @endif
                                </div>
                            </x-form-modal>
                        </div>
                    </div>
                </x-container>

                <x-container>
                    <x-heading type="h2">{{ __('profile.update_password.heading') }}</x-heading>
                    <x-paragraph>{{ __('profile.update_password.description') }}</x-paragraph>

                    <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}"
                        class="mt-4">
                        <x-primary-button :href="route('profile.edit', ['modal' => 'profile-password'])"
                            data-form-modal-trigger data-form-modal-name="profile-password-form-modal"
                            data-form-modal-param="profile-password">
                            {{ __('profile.actions.update_password') }}
                        </x-primary-button>

                        <x-form-modal name="profile-password-form-modal" title="{{ __('profile.update_password.heading') }}"
                            description="{{ __('profile.update_password.description') }}" max-width="2xl"
                            :show="$modalForm === 'profile-password'">
                            <div class="p-6" x-ref="content">
                                @if ($modalForm === 'profile-password')
                                    @include('profile.partials.update-password-form', ['modal' => true])
                                @else
                                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                                    <div x-html="content"></div>
                                @endif
                            </div>
                        </x-form-modal>
                    </div>
                </x-container>

                <x-secondary-button :href="route('profile.edit', ['full' => 1])">
                    {{ __('profile.actions.open_full_page') }}
                </x-secondary-button>
            @endif

            @include('profile.partials.api-configuration')
            @include('profile.partials.api-docs')
            @include('profile.partials.delete-user-form')
        </div>
    </x-main>
</x-app-layout>
