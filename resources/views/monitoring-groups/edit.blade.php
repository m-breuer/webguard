@if (request()->boolean('modal'))
    @include('monitoring-groups._modal-form', ['action' => route('monitoring-groups.update', $monitoringGroup)])
@else
<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('monitoring_group.edit.title', ['group' => $monitoringGroup->name]) }}</x-heading>

        <x-secondary-button :href="route('monitoring-groups.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container>
            <form method="POST" action="{{ route('monitoring-groups.update', $monitoringGroup) }}">
                @include('monitoring-groups._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
@endif
