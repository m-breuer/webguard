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
            <div class="flex items-center gap-2">
                <x-secondary-button :href="route('admin.packages.edit', $package)" :icon-only="true"
                    data-form-modal-trigger data-form-modal-name="admin-package-form-modal"
                    title="{{ __('button.edit') }}" aria-label="{{ __('button.edit') }}">
                    <x-icon name="pencil" class="h-4 w-4" />
                </x-secondary-button>
                <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="inline-flex"
                data-confirm-message="{{ __('admin.packages.messages.confirm_delete') }}">
                @csrf
                @method('DELETE')
                    <x-danger-button :icon-only="true" title="{{ __('button.delete') }}" aria-label="{{ __('button.delete') }}">
                        <x-icon name="trash" class="h-4 w-4" />
                    </x-danger-button>
                </form>
            </div>
        </x-table.cell>
    </x-table.row>
@empty
    <x-table.row>
        <x-table.cell colSpan="4" class="text-center">
            {{ __('admin.packages.messages.no_packages') }}
        </x-table.cell>
    </x-table.row>
@endforelse
