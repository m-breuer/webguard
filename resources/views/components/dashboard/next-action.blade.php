@props([
    'recommendedAction',
    'recommendedHref',
    'quickActions',
    'stateTone',
])

<section class="rounded-3xl border {{ $stateTone['border'] }} {{ $stateTone['soft'] }} p-6 sm:p-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.12em] text-purple-700 dark:text-purple-300">{{ __('dashboard.next_action.heading') }}</p>
            <x-heading type="h2" class="mt-2">{{ __('dashboard.recommended.' . $recommendedAction) }}</x-heading>
        </div>
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-purple-600 shadow-sm dark:bg-gray-800 dark:text-purple-300" aria-hidden="true">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
            </svg>
        </span>
    </div>

    <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ __('dashboard.next_action.description') }}</p>
    <x-dashboard.action-link :href="$recommendedHref" variant="solid" class="mt-6"
        :modal-name="$recommendedAction === 'create' ? 'monitoring-form-modal' : null">
        {{ __('dashboard.recommended.label') }}
    </x-dashboard.action-link>

    <div class="mt-6 border-t border-purple-200/80 pt-5 dark:border-purple-900/60">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">{{ __('dashboard.quick_actions.heading') }}</p>
        <div class="mt-3 grid gap-1 sm:grid-cols-2 xl:grid-cols-1">
            @foreach ($quickActions as $action)
                @if ($action['visible'])
                    <x-dashboard.action-link :href="$action['href']" variant="list"
                        :modal-name="$action['key'] === 'create' ? 'monitoring-form-modal' : null">
                        {{ __('dashboard.quick_actions.' . $action['key']) }}
                    </x-dashboard.action-link>
                @endif
            @endforeach
        </div>
    </div>
</section>
