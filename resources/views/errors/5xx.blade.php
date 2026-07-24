@php
    $status = (int) ($exception?->getStatusCode() ?? 500);
    $errorKey = in_array($status, [500, 503], true) ? $status : 500;
    $error = __('errors.status.' . $errorKey);
@endphp

@include('errors.layout')
