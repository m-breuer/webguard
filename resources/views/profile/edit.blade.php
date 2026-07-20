<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ __('profile.title') }}</x-heading>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('profile.information.description') }}</p>
        </div>
    </x-slot>

    <x-main>
        <div data-profile-settings class="space-y-6">
            @if ($fullPage)
                <div class="grid gap-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:items-start">
                    <nav aria-label="{{ __('profile.navigation.heading') }}" class="lg:sticky lg:top-6">
                        <p class="px-3 text-xs font-bold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">{{ __('profile.navigation.heading') }}</p>
                        <div class="mt-3 space-y-1 text-sm font-semibold">
                            <a href="#profile-information" class="block rounded-lg px-3 py-2 text-purple-700 hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-950/30">{{ __('profile.navigation.account') }}</a>
                            <a href="#profile-password" class="block rounded-lg px-3 py-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('profile.navigation.security') }}</a>
                            <a href="#profile-api" class="block rounded-lg px-3 py-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('profile.navigation.api') }}</a>
                            <a href="#profile-delete" class="block rounded-lg px-3 py-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('profile.navigation.danger_zone') }}</a>
                        </div>
                    </nav>

                    <div class="min-w-0 space-y-6">
                        <section id="profile-information" class="scroll-mt-6">
                            <x-container>
                                @include('profile.partials.update-profile-information-form')
                            </x-container>
                        </section>
                        <section id="profile-password" class="scroll-mt-6">
                            <x-container>
                                @include('profile.partials.update-password-form')
                            </x-container>
                        </section>
                        <section id="profile-api" class="scroll-mt-6">
                            @include('profile.partials.api-configuration')
                            @include('profile.partials.api-docs')
                        </section>
                        <section id="profile-delete" class="scroll-mt-6">
                            @include('profile.partials.delete-user-form')
                        </section>
                    </div>
                </div>
            @else
                <section data-profile-section="account">
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
                </section>

                <section data-profile-section="security">
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
                </section>

                <x-secondary-button :href="route('profile.edit', ['full' => 1])">
                    {{ __('profile.actions.open_full_page') }}
                </x-secondary-button>
            @endif

            <section data-profile-section="api">
                @include('profile.partials.api-configuration')
                @include('profile.partials.api-docs')
            </section>
            <section data-profile-section="danger-zone">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </x-main>
</x-app-layout>
