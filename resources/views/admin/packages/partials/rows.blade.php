@forelse ($packages as $package)
    <x-table.row>
        <x-table.cell>{{ $package->monitoring_limit }}</x-table.cell>
        <x-table.cell>{{ $package->price }}</x-table.cell>
        <x-table.cell>
            @if ($package->is_selectable)
                <span class="text-green-500">{{ __('admin.packages.fields.yes') }}</span>
            @else
                <span class="text-red-500">{{ __('admin.packages.fields.no') }}</span>
            @endif
        </x-table.cell>
        <x-table.cell>
            <a href="{{ route('admin.packages.edit', $package) }}" class="text-purple-600 hover:underline">
                {{ __('button.edit') }}
            </a>
            <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('admin.packages.messages.confirm_delete') }}')"
                    class="ml-2 text-red-600 hover:underline">{{ __('button.delete') }}</button>
            </form>
        </x-table.cell>
    </x-table.row>
@empty
    <x-table.row>
        <x-table.cell colSpan="4" class="text-center">
            {{ __('admin.packages.messages.no_packages') }}
        </x-table.cell>
    </x-table.row>
@endforelse
