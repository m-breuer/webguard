<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ __('profile.title') }}</x-heading>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('profile.information.description') }}</p>
        </div>
    </x-slot>

    <x-main>
        <div data-profile-settings class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-start">
            <aside class="lg:sticky lg:top-6">
                <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="px-3 py-2 text-xs font-bold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                        {{ __('profile.navigation.heading') }}
                    </p>
                    <nav aria-label="{{ __('profile.navigation.heading') }}" class="mt-1 grid gap-1 text-sm font-semibold sm:grid-cols-2 lg:block">
                        <a href="#profile-information" class="rounded-lg bg-purple-50 px-3 py-2 text-purple-700 transition hover:bg-purple-100 dark:bg-purple-950/30 dark:text-purple-300 dark:hover:bg-purple-950/50">
                            {{ __('profile.navigation.account') }}
                        </a>
                        <a href="#profile-password" class="rounded-lg px-3 py-2 text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                            {{ __('profile.navigation.security') }}
                        </a>
                        <a href="#profile-api" class="rounded-lg px-3 py-2 text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                            {{ __('profile.navigation.api') }}
                        </a>
                        <a href="#profile-delete" class="rounded-lg px-3 py-2 text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                            {{ __('profile.navigation.danger_zone') }}
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="min-w-0 space-y-6">
                <section id="profile-information" data-profile-section="account" class="scroll-mt-6">
                    <x-container class="space-y-6">
                        @include('profile.partials.update-profile-information-form', ['modal' => $modalForm === 'profile-information'])
                    </x-container>
                </section>
                <section id="profile-password" data-profile-section="security" class="scroll-mt-6">
                    <x-container class="space-y-6">
                        @include('profile.partials.update-password-form', ['modal' => $modalForm === 'profile-password'])
                    </x-container>
                </section>
                <section id="profile-api" data-profile-section="api" class="scroll-mt-6 space-y-6">
                    <x-container class="space-y-6">
                        @include('profile.partials.api-configuration')
                    </x-container>
                    <x-container class="space-y-4">
                        @include('profile.partials.api-docs')
                    </x-container>
                </section>
                <section id="profile-delete" data-profile-section="danger-zone" class="scroll-mt-6">
                    <x-container class="space-y-4">
                        @include('profile.partials.delete-user-form')
                    </x-container>
                </section>
            </div>
        </div>
    </x-main>
</x-app-layout>
