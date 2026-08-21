@props(['name'])

<svg
    {{ $attributes->merge(['class' => 'h-5 w-5']) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch ($name)
        @case ('home')
            <path d="m3 10 9-7 9 7" />
            <path d="M5 9.5V21h14V9.5M9 21v-6h6v6" />
            @break
        @case ('monitoring')
            <path d="M4 5h16v12H4z" />
            <path d="m7 13 3-3 2 2 4-4" />
            <path d="M8 21h8M12 17v4" />
            @break
        @case ('groups')
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20a6 6 0 0 1 12 0" />
            <path d="M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 4 6" />
            @break
        @case ('status-pages')
            <path d="M4 5h16v14H4z" />
            <path d="M8 9h8M8 13h5" />
            @break
        @case ('incidents')
            <path d="M12 3 3 20h18L12 3Z" />
            <path d="M12 9v5M12 17h.01" />
            @break
        @case ('maintenance')
            <path d="m14.7 6.3 3-3a4 4 0 0 0-5 5L4 17a2.1 2.1 0 0 0 3 3l8.8-8.7a4 4 0 0 0 5-5l-3 3-3.1-.9-.9-3.1Z" />
            @break
        @case ('teams')
            <circle cx="9" cy="8" r="3" />
            <circle cx="17" cy="9" r="2.25" />
            <path d="M3 20a6 6 0 0 1 12 0M15 15a5 5 0 0 1 6 5" />
            @break
        @case ('admin-dashboard')
            <rect x="4" y="4" width="6" height="6" rx="1" />
            <rect x="14" y="4" width="6" height="6" rx="1" />
            <rect x="4" y="14" width="6" height="6" rx="1" />
            <rect x="14" y="14" width="6" height="6" rx="1" />
            @break
        @case ('users')
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20a6 6 0 0 1 12 0M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 4 6" />
            @break
        @case ('packages')
            <path d="m4 8 8-4 8 4-8 4-8-4Z" />
            <path d="M4 8v8l8 4 8-4V8M12 12v8" />
            @break
        @case ('server-instances')
            <rect x="4" y="3" width="16" height="18" rx="2" />
            <path d="M8 7h.01M8 12h.01M8 17h.01M12 7h4M12 12h4M12 17h4" />
            @break
        @case ('api-access')
            <path d="m8 9-3 3 3 3M16 9l3 3-3 3M14 5l-4 14" />
            @break
        @case ('activity-logs')
            <path d="M5 4h14v16H5z" />
            <path d="M8 8h8M8 12h8M8 16h5" />
            @break
        @case ('eye')
            <path d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7Z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case ('external-link')
            <path d="M14 4h6v6" />
            <path d="m20 4-9 9" />
            <path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" />
            @break
        @case ('arrow-left')
            <path d="m15 18-6-6 6-6" />
            <path d="M9 12h12" />
            @break
        @case ('arrow-right')
            <path d="m9 18 6-6-6-6" />
            <path d="M3 12h12" />
            @break
        @case ('chart')
            <path d="M4 19V5M4 19h16" />
            <path d="m7 15 3-4 3 2 5-6" />
            @break
        @case ('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case ('copy')
            <rect x="9" y="9" width="11" height="11" rx="2" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
            @break
        @case ('globe')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />
            @break
        @case ('pencil')
            <path d="m4 16 8.5-8.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
            <path d="m13.5 6.5 4 4" />
            @break
        @case ('ellipsis')
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
            @break
        @case ('refresh')
            <path d="M20 11a8 8 0 0 0-14.5-4L4 9" />
            <path d="M4 4v5h5" />
            <path d="M4 13a8 8 0 0 0 14.5 4L20 15" />
            <path d="M20 20v-5h-5" />
            @break
        @case ('send')
            <path d="m22 2-7 20-4-9-9-4Z" />
            <path d="M22 2 11 13" />
            @break
        @case ('trash')
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
            @break
        @case ('x')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
    @endswitch
</svg>
