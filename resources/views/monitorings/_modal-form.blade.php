<div class="p-6">
    @if (isset($monitoring))
        @include('monitorings._ownership')
    @endif

    <form method="POST" action="{{ $action }}">
        <input type="hidden" name="modal_form" value="monitoring-{{ isset($monitoring) ? 'edit' : 'create' }}">
        @include('monitorings._form', ['modal' => true])
    </form>

    @if (isset($monitoring))
        <div class="mt-6">
            @include('monitorings._notification_preferences', ['fieldIdPrefix' => 'modal_edit_notification_preference'])
        </div>
    @endif
</div>
