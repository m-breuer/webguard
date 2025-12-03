@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringLifecycleStatus;
    use App\Enums\ServerInstance;
    use App\Enums\HttpMethod;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading>
            {{ __('monitoring.edit.title', ['monitoring' => $monitoring->name]) }}
            <small>({{ strtoupper($monitoring->type->value) }})</small>
        </x-heading>

        <x-secondary-button :href="route('monitorings.show', $monitoring)" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container>
            <form method="POST" action="{{ route('monitorings.update', $monitoring) }}">
                @include('monitorings._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
<script>
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        const textToCopy = element.innerText;
        navigator.clipboard.writeText(textToCopy).then(() => {
            alert('{{ __('app.messages.copied_to_clipboard') }}');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
</script>
