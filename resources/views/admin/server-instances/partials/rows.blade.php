@php
    use App\Enums\MonitoringType;
@endphp

@forelse ($instances as $instance)
    @php
        $healthStatus = $instance->healthStatus();
        $healthBadgeType = match ($healthStatus) {
            'healthy' => 'success',
            'stale' => 'warning',
            'inactive' => 'danger',
            default => 'info',
        };
        $monitoringsCount = (int) ($monitoringCounts->get($instance->code) ?? 0);
        $typeCounts = $monitoringTypeCounts->get($instance->code, collect());
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
        <x-table.cell>
            {{ trans_choice('admin.server_instances.monitorings_count', $monitoringsCount, ['count' => $monitoringsCount]) }}
        </x-table.cell>
        <x-table.cell>
            @if ($typeCounts->isEmpty())
                <span class="text-gray-500 dark:text-gray-400">
                    {{ __('admin.server_instances.fields.none') }}
                </span>
            @else
                <div class="flex flex-wrap gap-1">
                    @foreach (MonitoringType::cases() as $type)
                        @if ((int) ($typeCounts->get($type->value) ?? 0) > 0)
                            <x-badge type="info">
                                {{ __('monitoring.types.' . $type->value) }}:
                                {{ $typeCounts->get($type->value) }}
                            </x-badge>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-table.cell>
        <x-table.cell>{{ $instance->created_at->format('d.m.Y') }}</x-table.cell>
        <x-table.cell>{{ $instance->updated_at->format('d.m.Y') }}</x-table.cell>
        <x-table.cell>
            <a href="{{ route('admin.server-instances.edit', $instance) }}" class="text-purple-600 hover:underline">
                {{ __('button.edit') }}
            </a>
            <form action="{{ route('admin.server-instances.destroy', $instance) }}" method="POST" class="inline">
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
        <x-table.cell colSpan="9" class="text-center">
            {{ __('admin.server_instances.messages.no_instances') }}
        </x-table.cell>
    </x-table.row>
@endforelse
