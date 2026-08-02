@props([
    'value',
    'format' => 'datetime',
])

@php
    $date = \Illuminate\Support\Carbon::parse($value)->locale(app()->getLocale());
    $displayFormat = match ($format) {
        'date' => 'L',
        'month' => 'MMMM YYYY',
        'datetime_seconds' => 'L LTS',
        default => 'L LT',
    };
@endphp

<time {{ $attributes->merge(['datetime' => $date->toIso8601String(), 'title' => $date->isoFormat('LLLL')]) }}>
    {{ $date->isoFormat($displayFormat) }}
</time>
