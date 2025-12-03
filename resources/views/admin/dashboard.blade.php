<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.dashboard.heading') }}
        </x-heading>
    </x-slot>

    <x-main>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('admin.users.index') }}">
                <x-container class="h-full">
                    <x-heading type="h2" space="true">{{ __('admin.dashboard.users.heading') }}</x-heading>
                    <x-paragraph>{{ __('admin.dashboard.users.description') }}</x-paragraph>
                </x-container>
            </a>

            <a href="{{ route('admin.packages.index') }}">
                <x-container class="h-full">
                    <x-heading type="h2" space="true">{{ __('admin.dashboard.packages.heading') }}</x-heading>
                    <x-paragraph>{{ __('admin.dashboard.packages.description') }}</x-paragraph>
                </x-container>
            </a>

            <a href="{{ route('admin.apis.index') }}">
                <x-container class="h-full">
                    <x-heading type="h2" space="true">{{ __('admin.dashboard.apis.heading') }}</x-heading>
                    <x-paragraph>{{ __('admin.dashboard.apis.description') }}</x-paragraph>
                </x-container>
            </a>
        </div>
    </x-main>
</x-app-layout>
