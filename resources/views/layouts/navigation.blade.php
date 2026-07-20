@php
    $operationsNavigation = [
        ['label' => __('app.navigation.monitorings'), 'href' => route('monitorings.index'), 'active' => 'monitorings.*'],
        ['label' => __('app.navigation.monitoring_groups'), 'href' => route('monitoring-groups.index'), 'active' => 'monitoring-groups.*'],
        ['label' => __('app.navigation.status_pages'), 'href' => route('status-pages.index'), 'active' => 'status-pages.*'],
        ['label' => __('app.navigation.incidents'), 'href' => route('incidents.analytics'), 'active' => 'incidents.*'],
        ['label' => __('app.navigation.maintenance'), 'href' => route('maintenance.index'), 'active' => 'maintenance.*'],
    ];

    $collaborationNavigation = [
        ['label' => __('app.navigation.teams'), 'href' => route('teams.index'), 'active' => 'teams.*'],
    ];
@endphp

<nav x-data="{ open: false }"
    class="relative z-40 border-b border-purple-900/40 bg-purple-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:overflow-y-auto lg:border-b-0">
    <div class="flex min-h-16 items-center justify-between px-4 lg:min-h-screen lg:h-auto lg:flex-col lg:items-stretch lg:px-3 lg:py-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-2 py-1.5 focus:outline-hidden focus:ring-2 focus:ring-purple-300">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm">
                <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="{{ __('app.logo_alt') }}" class="h-full w-full object-contain">
            </span>
            <span class="text-lg font-bold tracking-tight text-white">{{ __('app.name') }}</span>
        </a>

        <div data-primary-navigation class="hidden flex-1 pt-12 lg:block">
            <a data-primary-destination href="{{ route('dashboard') }}"
                @class([
                    'flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-semibold transition focus:outline-hidden focus:ring-2 focus:ring-purple-300',
                    'border-purple-400/50 bg-purple-700 text-white shadow-lg shadow-purple-950/20' => request()->routeIs('dashboard'),
                    'border-transparent text-purple-100 hover:border-purple-700 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('dashboard'),
                ])>
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 13.5 12 5l8 8.5M6.5 11.5v7a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5v-7" />
                </svg>
                <span>{{ __('app.navigation.dashboard') }}</span>
            </a>

            <div data-secondary-navigation class="mt-8 space-y-6">
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.sections.operations') }}</p>
                    <div class="mt-2 space-y-1">
                        @foreach ($operationsNavigation as $item)
                            @php($itemActive = request()->routeIs($item['active']))
                            <a data-secondary-destination href="{{ $item['href'] }}"
                                @class([
                                    'group flex items-center gap-3 rounded-lg border px-3 py-2.5 text-sm font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300',
                                    'border-purple-400/30 bg-purple-900/70 text-white' => $itemActive,
                                    'border-transparent text-purple-200 hover:border-purple-800 hover:bg-purple-900/60 hover:text-white' => ! $itemActive,
                                ])>
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $itemActive ? 'bg-purple-200' : 'bg-purple-400/70 group-hover:bg-purple-200' }}"></span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.sections.collaboration') }}</p>
                    <div class="mt-2 space-y-1">
                        @foreach ($collaborationNavigation as $item)
                            @php($itemActive = request()->routeIs($item['active']))
                            <a data-secondary-destination href="{{ $item['href'] }}"
                                @class([
                                    'group flex items-center gap-3 rounded-lg border px-3 py-2.5 text-sm font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300',
                                    'border-purple-400/30 bg-purple-900/70 text-white' => $itemActive,
                                    'border-transparent text-purple-200 hover:border-purple-800 hover:bg-purple-900/60 hover:text-white' => ! $itemActive,
                                ])>
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $itemActive ? 'bg-purple-200' : 'bg-purple-400/70 group-hover:bg-purple-200' }}"></span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 hidden space-y-4 lg:block">
            <div data-notifications-navigation class="flex items-center gap-2">
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
                    <span class="sr-only">{{ __('notifications.title') }}</span>
                    @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                        <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
                <x-language-switch id="language-switch-desktop" align="left" placement="top" />
            </div>

            <div data-navigation-utilities>
            <x-dropdown align="right" placement="top" width="48" contentClasses="bg-white py-2 dark:bg-slate-900">
                <x-slot name="trigger">
                    <button id="profile-menu-desktop" class="flex w-full items-center justify-between gap-3 rounded-xl border border-purple-800 px-3 py-2 text-left text-sm font-medium text-purple-100 transition hover:border-purple-600 hover:bg-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-300">
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
        </div>

        <div class="flex items-center gap-2 lg:hidden">
            <a id="notifications-bell-mobile" href="{{ route('notifications.index') }}" aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-purple-800 text-purple-100 transition hover:bg-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a2.75 2.75 0 0 1-5.714 0m11.104-2.108c-1.086-1.3-1.747-2.95-1.747-4.755V9.5a6.5 6.5 0 0 0-13 0v.719c0 1.805-.661 3.455-1.747 4.755A1.25 1.25 0 0 0 4.713 17h14.574a1.25 1.25 0 0 0 .96-2.026Z" /></svg>
                @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                    <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                @endif
            </a>
            <x-language-switch id="language-switch-mobile" placement="bottom" />
            <button type="button" @click="open = ! open" class="inline-flex items-center justify-center rounded-xl border border-purple-800 p-2 text-purple-100 transition hover:bg-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-300" aria-label="{{ __('app.navigation.home') }}">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div data-mobile-navigation x-cloak x-show="open" x-transition class="border-t border-purple-900/60 px-4 pb-4 pt-3 lg:hidden">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.dashboard') }}</p>
        <a href="{{ route('dashboard') }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => request()->routeIs('dashboard'), 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! request()->routeIs('dashboard')])>{{ __('app.navigation.dashboard') }}</a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.sections.operations') }}</p>
        @foreach ($operationsNavigation as $item)
            @php($itemActive = request()->routeIs($item['active']))
            <a href="{{ $item['href'] }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => $itemActive, 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! $itemActive])>{{ $item['label'] }}</a>
        @endforeach

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-[0.16em] text-purple-300">{{ __('app.navigation.sections.collaboration') }}</p>
        @foreach ($collaborationNavigation as $item)
            @php($itemActive = request()->routeIs($item['active']))
            <a href="{{ $item['href'] }}" @class(['block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium transition focus:outline-hidden focus:ring-2 focus:ring-purple-300', 'bg-purple-700 text-white' => $itemActive, 'text-purple-100 hover:bg-purple-900/70 hover:text-white' => ! $itemActive])>{{ $item['label'] }}</a>
        @endforeach

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
