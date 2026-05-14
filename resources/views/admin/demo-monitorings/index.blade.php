<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.demo_monitorings.title') }}
        </x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-container>
                <x-heading type="h2">{{ __('admin.demo_monitorings.summary.demo_user') }}</x-heading>
                <p class="mt-2 font-semibold text-gray-900 dark:text-gray-100">{{ $demoUser->name }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $demoUser->email }}</p>
            </x-container>

            <x-container>
                <x-heading type="h2">{{ __('admin.demo_monitorings.summary.monitorings') }}</x-heading>
                <p class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-300">{{ $monitorings->total() }}</p>
            </x-container>

            <x-container>
                <x-heading type="h2">{{ __('admin.demo_monitorings.summary.package_limit') }}</x-heading>
                <p class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-300">{{ $demoUser->package?->monitoring_limit ?? '-' }}</p>
            </x-container>
        </div>

        <div class="mb-4 flex justify-end">
            <x-primary-button :href="route('admin.demo-monitorings.create')">
                {{ __('admin.demo_monitorings.actions.create') }}
            </x-primary-button>
        </div>

        <x-table>
            <x-slot name="head">
                <x-table.heading>{{ __('monitoring.index.table.name') }}</x-table.heading>
                <x-table.heading>{{ __('monitoring.index.table.type') }}</x-table.heading>
                <x-table.heading>{{ __('monitoring.index.table.target') }}</x-table.heading>
                <x-table.heading>{{ __('monitoring.index.table.status') }}</x-table.heading>
                <x-table.heading>{{ __('monitoring.form.preferred_location') }}</x-table.heading>
                <x-table.heading>{{ __('admin.demo_monitorings.fields.created_at') }}</x-table.heading>
                <x-table.heading>{{ __('admin.demo_monitorings.fields.actions') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @forelse ($monitorings as $monitoring)
                    <x-table.row>
                        <x-table.cell>{{ $monitoring->name }}</x-table.cell>
                        <x-table.cell>{{ __('monitoring.types.' . $monitoring->type->value) }}</x-table.cell>
                        <x-table.cell>
                            <span class="block max-w-xs truncate" title="{{ $monitoring->target }}">
                                {{ $monitoring->target }}
                            </span>
                        </x-table.cell>
                        <x-table.cell>{{ ucfirst($monitoring->status->value) }}</x-table.cell>
                        <x-table.cell>{{ $monitoring->preferred_location }}</x-table.cell>
                        <x-table.cell>{{ $monitoring->created_at?->format('Y-m-d H:i') }}</x-table.cell>
                        <x-table.cell>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.demo-monitorings.edit', $monitoring) }}"
                                    class="inline-flex min-h-10 items-center text-purple-600 hover:underline">
                                    {{ __('button.edit') }}
                                </a>
                                <form action="{{ route('admin.demo-monitorings.destroy', $monitoring) }}" method="POST"
                                    class="inline-flex"
                                    data-confirm-message="{{ __('admin.demo_monitorings.messages.confirm_delete') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex min-h-10 cursor-pointer items-center text-red-600 hover:underline"
                                        data-testid="delete-demo-monitoring-{{ $monitoring->id }}">
                                        {{ __('button.delete') }}
                                    </button>
                                </form>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colspan="7">
                            {{ __('admin.demo_monitorings.messages.empty') }}
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $monitorings->links() }}
        </div>
    </x-main>
</x-app-layout>
