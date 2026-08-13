<div class="p-6">
    <form method="POST" action="{{ $action }}">
        <input
            type="hidden"
            name="modal_form"
            value="monitoring-group-{{ isset($monitoringGroup) ? 'edit' : 'create' }}"
        />
        @include('monitoring-groups._form', ['modal' => true])
    </form>
</div>
