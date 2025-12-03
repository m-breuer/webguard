<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.packages.edit.title') }}
        </x-heading>
    </x-slot>

    <x-main>
        <x-container space="true">
            <form method="POST" action="{{ route('admin.packages.update', $package) }}">
                @method('PUT')
                @include('admin.packages._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
