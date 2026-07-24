@php
    $status = (int) ($exception?->getStatusCode() ?? 400);
    $errorKey = in_array($status, [400, 403, 404, 419, 429], true) ? $status : 400;
    $error = __('errors.status.' . $errorKey);
@endphp

@include('errors.layout')
