<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.packages.create.title') }}
        </x-heading>

        <x-secondary-button :href="route('admin.users.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container space="true">
            <form method="POST" action="{{ route('admin.packages.store') }}">
                @include('admin.packages._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
