<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.server_instances.title') }}
        </x-heading>

        <div class="flex items-center space-x-2">
            <x-primary-button :href="route('admin.server-instances.create')" class="sm:ml-auto">
                {{ __('button.create') }}
            </x-primary-button>

            <x-secondary-button :href="route('admin.dashboard')">
                {{ __('button.back') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <x-main>
        <x-table>
            <x-slot name="head">
                <x-table.heading>{{ __('admin.server_instances.fields.code') }}</x-table.heading>
                <x-table.heading>{{ __('admin.server_instances.fields.status') }}</x-table.heading>
                <x-table.heading>{{ __('admin.server_instances.fields.health') }}</x-table.heading>
                <x-table.heading>{{ __('admin.server_instances.fields.last_seen_at') }}</x-table.heading>
                <x-table.heading>{{ __('admin.server_instances.fields.created_at') }}</x-table.heading>
                <x-table.heading>{{ __('admin.server_instances.fields.updated_at') }}</x-table.heading>
                <x-table.heading>{{ __('admin.server_instances.fields.actions') }}</x-table.heading>
            </x-slot>
            <x-slot name="body">
                @forelse ($instances as $instance)
                    @php
                        $healthStatus = $instance->healthStatus();
                        $healthBadgeType = match ($healthStatus) {
                            'healthy' => 'success',
                            'stale' => 'warning',
                            'inactive' => 'danger',
                            default => 'info',
                        };
                    @endphp
                    <x-table.row>
                        <x-table.cell>{{ $instance->code }}</x-table.cell>
                        <x-table.cell>
                            @if ($instance->is_active)
                                <span class="text-green-500">{{ __('admin.server_instances.fields.active') }}</span>
                            @else
                                <span class="text-red-500">{{ __('admin.server_instances.fields.inactive') }}</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            <x-badge :type="$healthBadgeType">
                                {{ __('admin.server_instances.health.' . $healthStatus) }}
                            </x-badge>
                        </x-table.cell>
                        <x-table.cell>
                            @if ($instance->last_seen_at)
                                <span title="{{ $instance->last_seen_at->toDateTimeString() }}">
                                    {{ $instance->last_seen_at->diffForHumans() }}
                                </span>
                            @else
                                {{ __('admin.server_instances.fields.never') }}
                            @endif
                        </x-table.cell>
                        <x-table.cell>{{ $instance->created_at->format('d.m.Y') }}</x-table.cell>
                        <x-table.cell>{{ $instance->updated_at->format('d.m.Y') }}</x-table.cell>
                        <x-table.cell>
                            <a href="{{ route('admin.server-instances.edit', $instance) }}"
                                class="text-purple-600 hover:underline">{{ __('button.edit') }}</a>
                            <form action="{{ route('admin.server-instances.destroy', $instance) }}" method="POST"
                                class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('{{ __('admin.server_instances.messages.confirm_delete') }}')"
                                    class="ml-2 text-red-600 hover:underline">{{ __('button.delete') }}</button>
                            </form>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colSpan="7" class="text-center">
                            {{ __('admin.server_instances.messages.no_instances') }}
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-slot>
        </x-table>
    </x-main>
</x-app-layout>
