<form method="POST" action="{{ $action }}">
    @if (isset($instance))
        @method('PUT')
    @endif
    @include('admin.server-instances._form', ['modalForm' => $modalForm])
</form>
