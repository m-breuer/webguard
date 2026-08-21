@if (request()->boolean('modal'))
    @include('status-pages._form', [
        'action' => route('status-pages.update', $statusPage),
        'method' => 'PATCH',
        'submitLabel' => __('button.update'),
        'modal' => true,
    ])
@else
    <x-app-layout>
        <x-slot name="header">
            <x-heading type="h1">{{ __('status_page.edit.title', ['statusPage' => $statusPage->name]) }}</x-heading>
        </x-slot>

        <x-main>
            @include('status-pages._form', [
                'action' => route('status-pages.update', $statusPage),
                'method' => 'PATCH',
                'submitLabel' => __('button.update'),
            ])
        </x-main>
    </x-app-layout>
@endif
