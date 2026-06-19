<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('monitoring_group.title') }}</x-heading>

        @if (!Auth::user()->isDemo())
            <x-primary-button :href="route('monitoring-groups.create')" class="sm:ml-auto">
                {{ __('button.create') }}
            </x-primary-button>
        @endif
    </x-slot>

    <x-main>
        @if ($monitoringGroups->isEmpty())
            <x-container class="text-center">
                <x-heading type="h2">{{ __('monitoring_group.empty.title') }}</x-heading>
                <x-paragraph space="true">{{ __('monitoring_group.empty.text') }}</x-paragraph>
                @if (!Auth::user()->isDemo())
                    <x-primary-button :href="route('monitoring-groups.create')">
                        {{ __('button.create') }}
                    </x-primary-button>
                @endif
            </x-container>
        @else
            <div class="space-y-4">
                @foreach ($monitoringGroups as $monitoringGroup)
                    <x-container>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <x-heading type="h2">{{ $monitoringGroup->name }}</x-heading>
                                @if ($monitoringGroup->description)
                                    <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                                        {{ $monitoringGroup->description }}
                                    </p>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ trans_choice('monitoring_group.monitorings_count', $monitoringGroup->monitorings_count, ['count' => $monitoringGroup->monitorings_count]) }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if (!Auth::user()->isDemo())
                                    <form method="POST" action="{{ route('monitoring-groups.publish-status-page', $monitoringGroup) }}">
                                        @csrf
                                        <x-secondary-button>
                                            {{ __('monitoring_group.actions.publish_status_page') }}
                                        </x-secondary-button>
                                    </form>
                                    <x-secondary-button :href="route('monitoring-groups.edit', $monitoringGroup)">
                                        {{ __('button.edit') }}
                                    </x-secondary-button>
                                    <form method="POST" action="{{ route('monitoring-groups.destroy', $monitoringGroup) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button
                                            x-data
                                            x-on:click.prevent="if (confirm('{{ __('monitoring_group.actions.delete.confirmation') }}')) $el.closest('form').submit()">
                                            {{ __('button.delete') }}
                                        </x-danger-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </x-container>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $monitoringGroups->links() }}
            </div>
        @endif
    </x-main>
</x-app-layout>
