@if (request()->boolean('modal'))
    @include('admin.users._modal-form', [
        'action' => route('admin.users.store'),
        'modalForm' => 'admin-user-create',
    ])
@else
<x-app-layout>
    <x-slot name="header">
        <x-heading>{{ __('user.actions.create') }}</x-heading>

        <x-secondary-button :href="route('admin.users.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container>
            <form method="POST" action="{{ route('admin.users.store') }}">
                @include('admin.users._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
@endif
