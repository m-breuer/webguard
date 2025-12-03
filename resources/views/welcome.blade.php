<x-guest-layout>
    <div class="not-dark:bg-white relative isolate overflow-hidden p-6 text-center">
        <div class="mx-auto max-w-2xl">
            <x-heading type="h1" class="text-4xl font-bold tracking-tight sm:text-6xl">
                {{ __('welcome.teaser.title') }}
            </x-heading>
            <x-paragraph class="mt-6 text-lg leading-8">
                {{ __('welcome.teaser.text') }}
            </x-paragraph>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-6">
                <a href="{{ route('register') }}"
                    class="rounded-md px-6 py-3 font-semibold text-gray-900 outline-1 outline-purple-600 dark:text-white">
                    {{ __('welcome.getting_started.register_now') }}
                </a>
                <a href="{{ route('login') }}"
                    class="rounded-md px-6 py-3 font-semibold text-gray-900 outline-1 outline-purple-600 dark:text-white">
                    {{ __('welcome.getting_started.already_have_account') }}
                </a>
                <a href="{{ route('demo') }}"
                    class="rounded-md bg-purple-600 px-6 py-3 font-semibold text-white shadow-md hover:bg-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600">
                    {{ __('welcome.getting_started.try_demo') }} <x-span aria-hidden="true">&rarr;</x-span>
                </a>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-12 md:grid-cols-3">
            <div>
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                    {{ __('welcome.features.1.badge') }}
                </div>
                <x-heading type="h2" class="text-lg font-semibold text-gray-800">
                    {{ __('welcome.features.1.title') }}
                </x-heading>
                <x-paragraph class="mt-2">{{ __('welcome.features.1.text') }}</x-paragraph>
            </div>
            <div>
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                    {{ __('welcome.features.2.badge') }}
                </div>
                <x-heading type="h2" class="text-lg font-semibold text-gray-800">
                    {{ __('welcome.features.2.title') }}
                </x-heading>
                <x-paragraph class="mt-2">{{ __('welcome.features.2.text') }}</x-paragraph>
            </div>
            <div>
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                    {{ __('welcome.features.3.badge') }}
                </div>
                <x-heading type="h2" class="text-lg font-semibold text-gray-800">
                    {{ __('welcome.features.3.title') }}
                </x-heading>
                <x-paragraph class="mt-2">{{ __('welcome.features.3.text') }}</x-paragraph>
            </div>
        </div>
    </div>
</x-guest-layout>
