@if (request()->boolean('modal'))
    <div class="p-6">
        @include('status-pages._form', [
            'action' => route('status-pages.store'),
            'submitLabel' => __('button.create'),
            'modal' => true,
        ])
    </div>
@else
<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('status_page.create.title') }}</x-heading>
    </x-slot>

    <x-main>
        @include('status-pages._form', [
            'action' => route('status-pages.store'),
            'submitLabel' => __('button.create'),
        ])
    </x-main>
</x-app-layout>
@endif
