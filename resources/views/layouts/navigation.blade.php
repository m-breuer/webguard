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

                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')" class="relative">
                        {{ __('notifications.title') }}
                        @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                            <span
                                class="absolute right-0 top-0 -translate-y-1/2 translate-x-3/4 transform rounded-full bg-red-500 px-2 py-1 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                        @endif
                    </x-nav-link>

                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('admin.title') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center">

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
                        @if (!Auth::user()->isGuest())
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

            <div class="flex items-center sm:hidden">
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

            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                {{ __('notifications.title') }}
                @if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                    <span
                        class="ms-2 inline-flex items-center justify-center rounded-full bg-red-500 px-2 py-1 text-xs font-bold leading-none text-white">{{ $unreadNotificationsCount }}</span>
                @endif
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
                @if (!Auth::user()->isGuest())
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
