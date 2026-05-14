<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ $statusPage->name }}</x-heading>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                    {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                </x-badge>
                @if ($statusPage->is_public)
                    <a href="{{ route('public-status-pages.show', $statusPage->slug) }}" target="_blank"
                        class="break-all hover:text-gray-700 dark:hover:text-white">
                        {{ route('public-status-pages.show', $statusPage->slug) }}
                    </a>
                @endif
            </div>
        </div>

        <div class="ml-auto flex flex-wrap gap-2">
            @if (!Auth::user()->isDemo())
                <x-secondary-button :href="route('status-pages.edit', $statusPage)">
                    {{ __('button.edit') }}
                </x-secondary-button>
                <form method="POST" action="{{ route('status-pages.destroy', $statusPage) }}"
                    data-confirm-message="{{ __('status_page.actions.delete_confirmation') }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>
                        {{ __('button.delete') }}
                    </x-danger-button>
                </form>
            @endif
            <x-secondary-button :href="route('status-pages.index')">
                {{ __('button.back') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <x-main>
        <div class="space-y-4">
            @if ($statusPage->description)
                <x-container>
                    <x-paragraph>{{ $statusPage->description }}</x-paragraph>
                </x-container>
            @endif

            @foreach ($statusPage->components as $statusPageComponent)
                <x-container>
                    <x-heading type="h2">{{ $statusPageComponent->name }}</x-heading>
                    @if ($statusPageComponent->description)
                        <x-paragraph space="true">{{ $statusPageComponent->description }}</x-paragraph>
                    @endif

                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($statusPageComponent->monitorings as $monitoring)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div>
                                    <a href="{{ route('monitorings.show', $monitoring) }}"
                                        class="font-medium text-gray-900 hover:text-purple-600 dark:text-gray-100 dark:hover:text-purple-300">
                                        {{ $monitoring->name }}
                                    </a>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('monitoring.types.' . $monitoring->type->value) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-container>
            @endforeach
        </div>
    </x-main>
</x-app-layout>
