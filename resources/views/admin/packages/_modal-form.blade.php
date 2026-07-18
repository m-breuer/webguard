<form method="POST" action="{{ $action }}">
    @if (isset($package))
        @method('PUT')
    @endif
    @include('admin.packages._form', ['modalForm' => $modalForm])
</form>
