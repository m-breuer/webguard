@props([
    'variant' => 'section',
])

<div {{ $attributes->merge(['class' => 'animate-pulse']) }}
    data-loading-skeleton="{{ $variant }}"
    role="status"
    aria-live="polite"
    aria-label="{{ __('app.messages.loading') }}">
    <span class="sr-only">{{ __('app.messages.loading') }}</span>

    @if ($variant === 'dashboard')
        <div class="space-y-6">
            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-7">
                <div class="h-5 w-32 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                <div class="mt-5 h-9 max-w-md rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                <div class="mt-3 h-4 max-w-2xl rounded-full bg-gray-200 dark:bg-gray-700"></div>
                <div class="mt-2 h-4 max-w-xl rounded-full bg-gray-200 dark:bg-gray-700"></div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="h-11 w-full rounded-xl bg-gray-200 dark:bg-gray-700 sm:max-w-md"></div>
                <div class="flex gap-2">
                    <div class="h-10 w-20 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-10 w-24 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-10 w-24 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                </div>
            </div>

            <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,24rem)]">
                <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                    <div class="h-5 w-48 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="mt-2 h-3 w-28 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="mt-6 space-y-3">
                        @foreach (range(1, 5) as $row)
                            <div class="flex items-center gap-3 rounded-2xl border border-gray-100 p-4 dark:border-gray-700">
                                <div class="h-3 w-3 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="h-4 w-2/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-3 w-3/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                                <div class="h-4 w-16 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="hidden rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:block">
                    <div class="h-5 w-32 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="mt-6 space-y-4">
                        <div class="h-24 rounded-2xl bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-4 w-4/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-4 w-3/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="h-6 w-2/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-4 w-3/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="space-y-3 pt-2">
                @foreach (range(1, 4) as $row)
                    <div class="flex items-center gap-4 rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="h-4 w-2/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-3 w-3/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="hidden h-4 w-20 rounded-full bg-gray-200 dark:bg-gray-700 sm:block"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
