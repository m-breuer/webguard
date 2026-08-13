<div class="p-6">
    @if (isset($monitoring))
        @include('monitorings._ownership')
    @endif

    <form id="monitoring-modal-form" method="POST" action="{{ $action }}">
        <input type="hidden" name="modal_form" value="monitoring-{{ isset($monitoring) ? 'edit' : 'create' }}" />
        @include('monitorings._form', [
            'modal' => true,
            'notificationPreferencesFormId' => isset($monitoring) ? 'modal-edit-notification-preferences-form' : null,
            'fieldIdPrefix' => 'modal_edit_notification_preference',
        ])
    </form>

    @if (isset($monitoring))
        <form
            id="modal-edit-notification-preferences-form"
            method="POST"
            action="{{ route('monitorings.notification-preferences.update', $monitoring) }}"
            class="hidden"
        >
            @csrf
            @method('PATCH')
        </form>
    @endif
</div>
