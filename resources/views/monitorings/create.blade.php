@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringLifecycleStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('monitoring.create.title') }}
        </x-heading>
        <x-secondary-button :href="route('monitorings.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>

        <x-container>
            <form method="POST" action="{{ route('monitorings.store') }}">
                @include('monitorings._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
