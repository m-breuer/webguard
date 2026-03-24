@props([
    'locale',
    'class' => 'h-5 w-5 rounded-full',
])

@php
    $normalizedLocale = strtolower((string) $locale);
    $clipPathId = 'flag-clip-' . uniqid();
@endphp

@if ($normalizedLocale === 'de')
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" {{ $attributes->merge(['class' => $class]) }}>
        <defs>
            <clipPath id="{{ $clipPathId }}">
                <circle cx="12" cy="12" r="12" />
            </clipPath>
        </defs>
        <g clip-path="url(#{{ $clipPathId }})">
            <rect width="24" height="8" y="0" fill="#111827" />
            <rect width="24" height="8" y="8" fill="#EF4444" />
            <rect width="24" height="8" y="16" fill="#FACC15" />
        </g>
    </svg>
@else
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" {{ $attributes->merge(['class' => $class]) }}>
        <defs>
            <clipPath id="{{ $clipPathId }}">
                <circle cx="12" cy="12" r="12" />
            </clipPath>
        </defs>
        <g clip-path="url(#{{ $clipPathId }})">
            <rect width="24" height="24" fill="#1E3A8A" />
            <path d="M0 0L24 24M24 0L0 24" stroke="#FFFFFF" stroke-width="5" />
            <path d="M0 0L24 24M24 0L0 24" stroke="#DC2626" stroke-width="2.5" />
            <path d="M12 0V24M0 12H24" stroke="#FFFFFF" stroke-width="7" />
            <path d="M12 0V24M0 12H24" stroke="#DC2626" stroke-width="4" />
        </g>
    </svg>
@endif
