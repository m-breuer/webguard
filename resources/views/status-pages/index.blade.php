<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('status_page.title') }}</x-heading>

        @if (!Auth::user()->isDemo())
            <x-primary-button :href="route('status-pages.create')" class="sm:ml-auto">
                {{ __('button.create') }}
            </x-primary-button>
        @endif
    </x-slot>

    <x-main>
        @if ($statusPages->isEmpty())
            <x-container class="text-center">
                <x-heading type="h2">{{ __('status_page.empty.title') }}</x-heading>
                <x-paragraph space="true">{{ __('status_page.empty.text') }}</x-paragraph>
                @if (!Auth::user()->isDemo())
                    <x-primary-button :href="route('status-pages.create')">
                        {{ __('button.create') }}
                    </x-primary-button>
                @endif
            </x-container>
        @else
            <div class="space-y-4">
                @foreach ($statusPages as $statusPage)
                    <x-container>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <x-heading type="h2">{{ $statusPage->name }}</x-heading>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                                        {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                                    </x-badge>
                                    <span>{{ trans_choice('status_page.components_count', $statusPage->components_count, ['count' => $statusPage->components_count]) }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if ($statusPage->is_public)
                                    <x-secondary-button :href="route('public-status-pages.show', $statusPage)" target="_blank">
                                        {{ __('status_page.actions.public_page') }}
                                    </x-secondary-button>
                                @endif
                                <x-secondary-button :href="route('status-pages.show', $statusPage)">
                                    {{ __('button.show') }}
                                </x-secondary-button>
                            </div>
                        </div>
                    </x-container>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $statusPages->links() }}
            </div>
        @endif
    </x-main>
</x-app-layout>
