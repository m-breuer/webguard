@php
    $workspaceNavActive = request()->routeIs('monitorings.*')
        || request()->routeIs('incidents.*')
        || request()->routeIs('maintenance.*')
        || request()->routeIs('monitoring-groups.*')
        || request()->routeIs('status-pages.*')
        || request()->routeIs('teams.*');
@endphp

<nav x-data="{ open: false, workspaceOpen: false }"
    class="relative z-40 border-b border-purple-900/40 bg-purple-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:border-b-0">
    <div class="flex h-16 items-center justify-between px-4 lg:flex-col lg:items-stretch lg:px-3 lg:py-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-2 py-1.5 focus:outline-hidden focus:ring-2 focus:ring-purple-300">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm">
                <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="{{ __('app.logo_alt') }}" class="h-full w-full object-contain">
            </span>
            <span class="text-lg font-bold tracking-tight text-white">{{ __('app.name') }}</span>
        </a>

        <div class="hidden flex-1 pt-12 lg:block">
            <a href="{{ route('dashboard') }}"
                @class([
                    'flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-semibold transition focus:outline-hidden focus:ring-2 focus:ring-purple-300',
                    'border-purple-400/50 bg-purple-700 text-white shadow-lg shadow-purple-950/20' => request()->routeIs('dashboard'),
                    'border-transparent text-purple-100 hover:border-purple-700 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('dashboard'),
                ])>
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 13.5 12 5l8 8.5M6.5 11.5v7a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5v-7" />
                </svg>
                <span>{{ __('app.navigation.signal_room') }}</span>
            </a>

            <div class="mt-5">
                <button type="button" @click="workspaceOpen = ! workspaceOpen"
                    @class([
                        'flex w-full items-center justify-between rounded-xl border px-4 py-3 text-left text-sm font-semibold transition focus:outline-hidden focus:ring-2 focus:ring-purple-300',
                        'border-purple-700 bg-purple-900/70 text-white' => $workspaceNavActive,
                        'border-transparent text-purple-100 hover:border-purple-700 hover:bg-purple-900/70 hover:text-white' => ! $workspaceNavActive,
                    ])>
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h4A2.5 2.5 0 0 1 13 6.5v4a2.5 2.5 0 0 1-2.5 2.5h-4A2.5 2.5 0 0 1 4 10.5v-4Zm7 7A2.5 2.5 0 0 1 13.5 11h4a2.5 2.5 0 0 1 2.5 2.5v4a2.5 2.5 0 0 1-2.5 2.5h-4a2.5 2.5 0 0 1-2.5-2.5v-4Z" />
                        </svg>
                        <span>{{ __('app.navigation.workspace') }}</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="{ 'rotate-180': workspaceOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-cloak x-show="workspaceOpen || {{ $workspaceNavActive ? 'true' : 'false' }}" x-transition class="mt-2 space-y-1 border-s border-purple-800 ps-3">
                    <a href="{{ route('monitorings.index') }}" @class(['block rounded-lg px-3 py-2 text-sm text-purple-100 transition hover:bg-purple-900/70 hover:text-white' => true, 'bg-purple-800 text-white' => request()->routeIs('monitorings.*')])>
                        {{ __('monitoring.title') }}
                    </a>
                    <a href="{{ route('incidents.analytics') }}" @class(['block rounded-lg px-3 py-2 text-sm text-purple-100 transition hover:bg-purple-900/70 hover:text-white' => true, 'bg-purple-800 text-white' => request()->routeIs('incidents.*')])>
                        {{ __('incidents.analytics.title') }}
                    </a>
                    <a href="{{ route('maintenance.index') }}" @class(['block rounded-lg px-3 py-2 text-sm text-purple-100 transition hover:bg-purple-900/70 hover:text-white' => true, 'bg-purple-800 text-white' => request()->routeIs('maintenance.*')])>
                        {{ __('maintenance.title') }}
                    </a>
                    <a href="{{ route('monitoring-groups.index') }}" @class(['block rounded-lg px-3 py-2 text-sm text-purple-100 transition hover:bg-purple-900/70 hover:text-white' => true, 'bg-purple-800 text-white' => request()->routeIs('monitoring-groups.*')])>
                        {{ __('monitoring_group.title') }}
                    </a>
                    <a href="{{ route('status-pages.index') }}" @class(['block rounded-lg px-3 py-2 text-sm text-purple-100 transition hover:bg-purple-900/70 hover:text-white' => true, 'bg-purple-800 text-white' => request()->routeIs('status-pages.*')])>
                        {{ __('status_page.title') }}
                    </a>
                    <a href="{{ route('teams.index') }}" @class(['block rounded-lg px-3 py-2 text-sm text-purple-100 transition hover:bg-purple-900/70 hover:text-white' => true, 'bg-purple-800 text-white' => request()->routeIs('teams.*')])>
                        {{ __('team.title') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="hidden space-y-3 lg:block">
            <div class="flex items-center gap-2">
                <a id="notifications-bell-desktop" href="{{ route('notifications.index') }}"
                    aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}"
                    @class([
                        'relative inline-flex h-10 w-10 items-center justify-center rounded-xl border transition focus:outline-hidden focus:ring-2 focus:ring-purple-300',
                        'border-purple-300 bg-purple-800 text-white' => request()->routeIs('notifications.*'),
                        'border-purple-800 text-purple-100 hover:border-purple-600 hover:bg-purple-900' => ! request()->routeIs('notifications.*'),
                    ])>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a2.75 2.75 0 0 1-5.714 0m11.104-2.108c-1.086-1.3-1.747-2.95-1.747-4.755V9.5a6.5 6.5 0 0 0-13 0v.719c0 1.805-.661 3.455-1.747 4.755A1.25 1.25 0 0 0 4.713 17h14.574a1.25 1.25 0 0 0 .96-2.026Z" />
                    </svg>
                    @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                        <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
                <x-language-switch id="language-switch-desktop" />
            </div>

            <x-dropdown align="right" width="48" contentClasses="bg-white py-2 dark:bg-slate-900">
                <x-slot name="trigger">
                    <button class="flex w-full items-center justify-between gap-3 rounded-xl border border-purple-800 px-3 py-2 text-left text-sm font-medium text-purple-100 transition hover:border-purple-600 hover:bg-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-300">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-700 font-bold text-white">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                            <span class="truncate">{{ Auth::user()->name }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    @if (!Auth::user()->isDemo())
                        <x-dropdown-link :href="route('profile.edit')">{{ __('profile.title') }}</x-dropdown-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <div class="border-t border-gray-100 px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-[0.08em] text-gray-400 dark:border-gray-700 dark:text-gray-500">{{ __('app.navigation.sections.administration') }}</div>
                        <x-dropdown-link :href="route('admin.dashboard')">{{ __('admin.dashboard.heading') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.users.index')">{{ __('admin.dashboard.users.heading') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.packages.index')">{{ __('admin.dashboard.packages.heading') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.server-instances.index')">{{ __('admin.dashboard.instances.heading') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.apis.index')">{{ __('admin.dashboard.apis.heading') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.activity-logs.index')">{{ __('admin.dashboard.activity_logs.heading') }}</x-dropdown-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" id="logout-form-desktop">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); document.getElementById('logout-form-desktop').submit();">{{ __('button.logout') }}</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <div class="flex items-center gap-2 lg:hidden">
            <a id="notifications-bell-mobile" href="{{ route('notifications.index') }}" aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-purple-800 text-purple-100 transition hover:bg-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a2.75 2.75 0 0 1-5.714 0m11.104-2.108c-1.086-1.3-1.747-2.95-1.747-4.755V9.5a6.5 6.5 0 0 0-13 0v.719c0 1.805-.661 3.455-1.747 4.755A1.25 1.25 0 0 0 4.713 17h14.574a1.25 1.25 0 0 0 .96-2.026Z" /></svg>
                @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                    <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                @endif
            </a>
            <x-language-switch id="language-switch-mobile" />
            <button type="button" @click="open = ! open" class="inline-flex items-center justify-center rounded-xl border border-purple-800 p-2 text-purple-100 transition hover:bg-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-300" aria-label="{{ __('app.navigation.home') }}">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div x-cloak x-show="open" x-transition class="border-t border-purple-900/60 px-4 pb-4 pt-3 lg:hidden">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.signal_room') }}</p>
        <a href="{{ route('dashboard') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('dashboard'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('dashboard')])>{{ __('app.navigation.signal_room') }}</a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.workspace') }}</p>
        <a href="{{ route('monitorings.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('monitorings.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('monitorings.*')])>{{ __('monitoring.title') }}</a>
        <a href="{{ route('incidents.analytics') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('incidents.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('incidents.*')])>{{ __('incidents.analytics.title') }}</a>
        <a href="{{ route('maintenance.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('maintenance.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('maintenance.*')])>{{ __('maintenance.title') }}</a>
        <a href="{{ route('monitoring-groups.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('monitoring-groups.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('monitoring-groups.*')])>{{ __('monitoring_group.title') }}</a>
        <a href="{{ route('status-pages.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('status-pages.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('status-pages.*')])>{{ __('status_page.title') }}</a>
        <a href="{{ route('teams.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('teams.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('teams.*')])>{{ __('team.title') }}</a>

        @if (Auth::user()->isAdmin())
            <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.sections.administration') }}</p>
            <a href="{{ route('admin.dashboard') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('admin.dashboard'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('admin.dashboard')])>{{ __('admin.dashboard.heading') }}</a>
            <a href="{{ route('admin.users.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('admin.users.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('admin.users.*')])>{{ __('admin.dashboard.users.heading') }}</a>
            <a href="{{ route('admin.packages.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('admin.packages.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('admin.packages.*')])>{{ __('admin.dashboard.packages.heading') }}</a>
            <a href="{{ route('admin.server-instances.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('admin.server-instances.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('admin.server-instances.*')])>{{ __('admin.dashboard.instances.heading') }}</a>
            <a href="{{ route('admin.apis.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('admin.apis.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('admin.apis.*')])>{{ __('admin.dashboard.apis.heading') }}</a>
            <a href="{{ route('admin.activity-logs.index') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('admin.activity-logs.*'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('admin.activity-logs.*')])>{{ __('admin.dashboard.activity_logs.heading') }}</a>
        @endif

        <div class="mt-3 border-t border-purple-900/60 pt-3">
            @if (!Auth::user()->isDemo())
                <a href="{{ route('profile.edit') }}" class="block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium text-purple-100 transition hover:bg-purple-900/70 hover:text-white focus:outline-hidden focus:ring-2 focus:ring-purple-300">{{ __('profile.title') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" id="logout-form-mobile">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();" class="block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium text-purple-100 transition hover:bg-purple-900/70 hover:text-white focus:outline-hidden focus:ring-2 focus:ring-purple-300">{{ __('button.logout') }}</a>
            </form>
        </div>
    </div>
</nav>
