@props([
    'heading',
    'description' => null,
    'href' => null,
    'viewAllLabel' => null,
])

<section {{ $attributes->class('overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800') }}>
    <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:px-6">
        <div>
            <x-heading type="h2" class="!text-lg !font-bold">{{ $heading }}</x-heading>
            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>

        @if ($href)
            <x-dashboard.action-link :href="$href" variant="quiet" class="shrink-0">
                {{ $viewAllLabel ?? __('dashboard.view_all') }}
            </x-dashboard.action-link>
        @elseif (isset($actions))
            {{ $actions }}
        @endif
    </div>

    {{ $slot }}
</section>
