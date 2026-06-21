<nav x-data="{ open: false }" class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="h-8 w-8">
                        <x-span class="ms-2 text-xl font-bold text-gray-800 dark:text-gray-100">
                            {{ __('app.name') }}
                        </x-span>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex sm:items-center">
                    <x-nav-link :href="route('monitorings.index')" :active="request()->routeIs('monitorings.*')">
                        {{ __('monitoring.title') }}
                    </x-nav-link>

                    <x-nav-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">
                        {{ __('maintenance.title') }}
                    </x-nav-link>

                    <x-nav-link :href="route('monitoring-groups.index')" :active="request()->routeIs('monitoring-groups.*')">
                        {{ __('monitoring_group.title') }}
                    </x-nav-link>

                    <x-nav-link :href="route('status-pages.index')" :active="request()->routeIs('status-pages.*')">
                        {{ __('status_page.title') }}
                    </x-nav-link>

                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('admin.title') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                <a id="notifications-bell-desktop" href="{{ route('notifications.index') }}"
                    aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}"
                    @class([
                        'relative inline-flex h-10 w-10 items-center justify-center rounded-full border transition focus:outline-hidden focus:ring-2 focus:ring-indigo-500',
                        'border-purple-500 bg-purple-50 text-purple-700 dark:border-purple-400 dark:bg-purple-900/30 dark:text-purple-200' => request()->routeIs('notifications.*'),
                        'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:border-gray-500 dark:hover:bg-gray-600' => ! request()->routeIs('notifications.*'),
                    ])>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M14.857 17.082a2.75 2.75 0 0 1-5.714 0m11.104-2.108c-1.086-1.3-1.747-2.95-1.747-4.755V9.5a6.5 6.5 0 0 0-13 0v.719c0 1.805-.661 3.455-1.747 4.755A1.25 1.25 0 0 0 4.713 17h14.574a1.25 1.25 0 0 0 .96-2.026Z" />
                    </svg>

                    @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                        <span
                            class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>

                <x-language-switch id="language-switch-desktop" />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="focus:outline-hidden inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-100">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if (!Auth::user()->isDemo())
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('profile.title') }}
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                                {{ __('button.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center gap-2 sm:hidden">
                <a id="notifications-bell-mobile" href="{{ route('notifications.index') }}"
                    aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}"
                    @class([
                        'relative inline-flex h-10 w-10 items-center justify-center rounded-full border transition focus:outline-hidden focus:ring-2 focus:ring-indigo-500',
                        'border-purple-500 bg-purple-50 text-purple-700 dark:border-purple-400 dark:bg-purple-900/30 dark:text-purple-200' => request()->routeIs('notifications.*'),
                        'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:border-gray-500 dark:hover:bg-gray-600' => ! request()->routeIs('notifications.*'),
                    ])>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M14.857 17.082a2.75 2.75 0 0 1-5.714 0m11.104-2.108c-1.086-1.3-1.747-2.95-1.747-4.755V9.5a6.5 6.5 0 0 0-13 0v.719c0 1.805-.661 3.455-1.747 4.755A1.25 1.25 0 0 0 4.713 17h14.574a1.25 1.25 0 0 0 .96-2.026Z" />
                    </svg>

                    @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                        <span
                            class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>

                <x-language-switch id="language-switch-mobile" />

                <button @click="open = ! open"
                    class="focus:outline-hidden inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('monitorings.index')" :active="request()->routeIs('monitorings.*')">
                {{ __('monitoring.title') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">
                {{ __('maintenance.title') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('monitoring-groups.index')" :active="request()->routeIs('monitoring-groups.*')">
                {{ __('monitoring_group.title') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('status-pages.index')" :active="request()->routeIs('status-pages.*')">
                {{ __('status_page.title') }}
            </x-responsive-nav-link>

            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('admin.title') }}
                </x-responsive-nav-link>
            @endif
        </div>
        <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if (!Auth::user()->isDemo())
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('profile.title') }}
                    </x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                        {{ __('button.logout') }}
                    </x-responsive-nav-link>
                </form>

            </div>
        </div>
    </div>
</nav>
