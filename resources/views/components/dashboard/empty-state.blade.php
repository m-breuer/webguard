@props([
    'canCreateMonitoring',
])

<section class="overflow-hidden rounded-3xl border border-purple-200 bg-white shadow-sm dark:border-purple-900/60 dark:bg-gray-800">
    <div class="grid gap-8 px-6 py-10 sm:px-10 sm:py-14 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
        <div>
            <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-100 text-3xl font-light text-purple-700 dark:bg-purple-950/50 dark:text-purple-300" aria-hidden="true">+</div>
            <x-heading type="h2">{{ __('dashboard.empty.title') }}</x-heading>
            <p class="mt-4 max-w-xl text-base leading-7 text-gray-600 dark:text-gray-300">{{ __('dashboard.empty.description') }}</p>
            @if ($canCreateMonitoring)
                <x-dashboard.action-link href="{{ route('monitorings.create') }}" variant="solid" class="mt-7 w-max">
                    {{ __('dashboard.quick_actions.create') }}
                </x-dashboard.action-link>
            @endif
        </div>

        <div class="hidden rounded-3xl bg-purple-50 p-8 dark:bg-purple-950/30 lg:block" aria-hidden="true">
            <div class="space-y-4">
                <div class="h-3 w-2/3 rounded-full bg-purple-200 dark:bg-purple-800"></div>
                <div class="h-3 w-full rounded-full bg-purple-100 dark:bg-purple-900"></div>
                <div class="h-20 rounded-2xl border border-purple-200 bg-white/80 dark:border-purple-800 dark:bg-gray-800/60"></div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="h-16 rounded-2xl bg-white/80 dark:bg-gray-800/60"></div>
                    <div class="h-16 rounded-2xl bg-white/80 dark:bg-gray-800/60"></div>
                    <div class="h-16 rounded-2xl bg-white/80 dark:bg-gray-800/60"></div>
                </div>
            </div>
        </div>
    </div>
</section>
