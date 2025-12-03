<x-guest-layout>
    <div class="not-dark:bg-white relative isolate overflow-hidden p-6 text-center">
        <x-heading type="h1" class="text-2xl font-semibold tracking-tight sm:text-4xl">
            {{ __('welcome.guest_login.title') }}
        </x-heading>
        <x-paragraph class="mb-6 mt-4 leading-8">
            {{ __('welcome.guest_login.text') }}
        </x-paragraph>
        <x-paragraph>
            <a href="{{ route('login', ['guest' => 'true']) }}"
                class="rounded-md bg-purple-600 px-6 py-3 font-semibold text-white shadow-md hover:bg-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600">
                {{ __('welcome.guest_login.button') }}
            </a>
        </x-paragraph>
    </div>
</x-guest-layout>
