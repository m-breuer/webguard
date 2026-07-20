@props(['name'])

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('eye')
            <path d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7Z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('external-link')
            <path d="M14 4h6v6" />
            <path d="m20 4-9 9" />
            <path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" />
            @break
        @case('arrow-left')
            <path d="m15 18-6-6 6-6" />
            <path d="M9 12h12" />
            @break
        @case('chart')
            <path d="M4 19V5M4 19h16" />
            <path d="m7 15 3-4 3 2 5-6" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('copy')
            <rect x="9" y="9" width="11" height="11" rx="2" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />
            @break
        @case('pencil')
            <path d="m4 16 8.5-8.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
            <path d="m13.5 6.5 4 4" />
            @break
        @case('ellipsis')
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
            @break
        @case('refresh')
            <path d="M20 11a8 8 0 0 0-14.5-4L4 9" />
            <path d="M4 4v5h5" />
            <path d="M4 13a8 8 0 0 0 14.5 4L20 15" />
            <path d="M20 20v-5h-5" />
            @break
        @case('send')
            <path d="m22 2-7 20-4-9-9-4Z" />
            <path d="M22 2 11 13" />
            @break
        @case('trash')
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
            @break
        @case('x')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
    @endswitch
</svg>
