<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.demo_monitorings.create.title') }}
        </x-heading>

        <x-secondary-button :href="route('admin.demo-monitorings.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container>
            <div class="mb-6">
                <x-heading type="h2">{{ __('admin.demo_monitorings.create.demo_user') }}</x-heading>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $demoUser->name }} - {{ $demoUser->email }}
                </p>
            </div>

            <form method="POST" action="{{ route('admin.demo-monitorings.store') }}">
                @include('monitorings._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
