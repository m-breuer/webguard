<x-marketing-layout>
    <x-slot:title>{{ __('monitoring_locations.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('monitoring_locations.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('monitoring_locations.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('monitoring_locations.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('monitoring_locations.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:canonical>{{ route('monitoring-locations') }}</x-slot:canonical>

    <main class="py-14 lg:py-20">
        <x-main class="w-full space-y-10 lg:space-y-12">
            <header class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-emerald-700 dark:text-emerald-300">
                    {{ __('monitoring_locations.hero.eyebrow') }}
                </x-paragraph>
                <x-heading type="h1" class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    {{ __('monitoring_locations.hero.title') }}
                </x-heading>
                <x-paragraph class="mt-4 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                    {{ __('monitoring_locations.hero.subtitle') }}
                </x-paragraph>
            </header>

            <section class="rounded-3xl border border-amber-200 bg-amber-50/80 p-8 dark:border-amber-900/50 dark:bg-amber-950/20 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('monitoring_locations.guidance.title') }}
                </x-heading>
                <x-paragraph class="mt-3 text-base leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('monitoring_locations.guidance.text') }}
                </x-paragraph>

                <x-paragraph class="mt-5 text-sm font-semibold uppercase tracking-[0.08em] text-slate-700 dark:text-slate-300">
                    {{ __('monitoring_locations.guidance.checklist_title') }}
                </x-paragraph>

                <ul class="mt-3 space-y-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ([1, 2, 3] as $item)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 inline-block h-2 w-2 flex-none rounded-full bg-amber-500 dark:bg-amber-300"></span>
                            <span>{{ __('monitoring_locations.guidance.items.' . $item) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white/90 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <caption class="sr-only">{{ __('monitoring_locations.table.caption') }}</caption>
                        <thead class="bg-slate-100/80 dark:bg-slate-900/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-600 dark:text-slate-300">
                                    {{ __('monitoring_locations.table.location') }}
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-600 dark:text-slate-300">
                                    {{ __('monitoring_locations.table.ip_range') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse ($locations as $location)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                        {{ \Illuminate\Support\Str::upper($location->code) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300">
                                        <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-800 dark:bg-slate-800 dark:text-slate-100">
                                            {{ $location->ip_address ?: __('monitoring_locations.table.ip_missing') }}
                                        </code>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-6 text-center text-sm text-slate-600 dark:text-slate-300">
                                        {{ __('monitoring_locations.table.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl border border-sky-200 bg-sky-50/80 p-8 dark:border-sky-900/50 dark:bg-sky-950/30 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('monitoring_locations.note.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-base leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('monitoring_locations.note.text') }}
                </x-paragraph>
            </section>
        </x-main>
    </main>
</x-marketing-layout>
