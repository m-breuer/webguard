<form method="POST" action="{{ $action }}" class="p-6">
    @include('teams._form', [
        'team' => $team ?? null,
        'modal' => true,
        'modalForm' => $modalForm,
    ])
</form>
