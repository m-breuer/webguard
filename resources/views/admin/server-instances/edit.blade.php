<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.server_instances.edit.title') }}
        </x-heading>

        <x-secondary-button :href="route('admin.server-instances.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container space="true">
            <form method="POST" action="{{ route('admin.server-instances.update', $instance) }}">
                @method('PUT')
                @include('admin.server-instances._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
