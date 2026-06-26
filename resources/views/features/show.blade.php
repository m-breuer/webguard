@php
    $featureKey = $feature['key'];
    $highlights = (array) trans('public_features.features.' . $featureKey . '.highlights');
    $details = (array) trans('public_features.features.' . $featureKey . '.details');
@endphp

<x-marketing-layout>
    <x-slot:title>{{ __('public_features.features.' . $featureKey . '.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('public_features.features.' . $featureKey . '.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('public_features.features.' . $featureKey . '.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('public_features.features.' . $featureKey . '.seo.title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('public_features.features.' . $featureKey . '.seo.description') }}</x-slot:ogDescription>
    <x-slot:canonical>{{ route('public-features.show', $slug) }}</x-slot:canonical>

    <main>
        <header class="border-b border-slate-200/80 dark:border-slate-800/70">
            <x-main class="grid w-full gap-10 py-14 lg:grid-cols-[1fr_0.48fr] lg:items-center lg:py-20">
                <div>
                    <a href="{{ route('public-features.index') }}"
                        class="inline-flex text-sm font-semibold text-purple-700 transition hover:text-purple-800 dark:text-purple-300 dark:hover:text-purple-200">
                        &lt;- {{ __('public_features.show.back') }}
                    </a>
                    <x-paragraph class="mt-6 text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">
                        {{ __('public_features.categories.' . $feature['category'] . '.title') }}
                    </x-paragraph>
                    <x-heading type="h1" class="mt-4 max-w-4xl text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                        {{ __('public_features.features.' . $featureKey . '.title') }}
                    </x-heading>
                    <x-paragraph class="mt-5 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        {{ __('public_features.features.' . $featureKey . '.lead') }}
                    </x-paragraph>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @if ($featureKey === 'rest_api')
                            <x-primary-button :href="route('scribe')"
                                class="bg-purple-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-purple-500/20 transition hover:bg-purple-600 focus:ring-purple-500 dark:bg-purple-400 dark:text-slate-950 dark:hover:bg-purple-300 dark:focus:ring-purple-300">
                                {{ __('public_features.common.api_docs') }}
                            </x-primary-button>
                        @else
                            <x-primary-button :href="route('register')"
                                class="bg-purple-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-purple-500/20 transition hover:bg-purple-600 focus:ring-purple-500 dark:bg-purple-400 dark:text-slate-950 dark:hover:bg-purple-300 dark:focus:ring-purple-300">
                                {{ __('public_features.common.get_started') }}
                            </x-primary-button>
                        @endif
                        <x-secondary-button :href="route('login', ['mode' => 'demo'])"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800">
                            {{ __('public_features.common.demo') }}
                        </x-secondary-button>
                    </div>
                </div>

                <aside class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-lg shadow-slate-300/20 dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-slate-950/20">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-300/15 dark:text-purple-300">
                        @include('features.partials.icon', ['featureKey' => $featureKey])
                    </div>
                    <x-heading type="h2" class="mt-5 text-xl font-semibold text-slate-900 dark:text-white">
                        {{ __('public_features.show.snapshot') }}
                    </x-heading>
                    <ul class="mt-4 space-y-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                        @foreach ($highlights as $highlight)
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 flex-none rounded-full bg-purple-500"></span>
                                <span>{{ $highlight }}</span>
                            </li>
                        @endforeach
                    </ul>
                </aside>
            </x-main>
        </header>

        <section class="py-14 lg:py-20">
            <x-main class="grid w-full gap-8 lg:grid-cols-[1fr_0.36fr]">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                        <x-heading type="h2" class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ __('public_features.show.how_it_helps') }}
                        </x-heading>
                        <div class="mt-5 space-y-4 text-base leading-8 text-slate-700 dark:text-slate-300">
                            @foreach ($details as $detail)
                                <x-paragraph>{{ $detail }}</x-paragraph>
                            @endforeach
                        </div>
                    </section>

                    @if (in_array($featureKey, ['rest_api', 'server_health'], true))
                        <section class="rounded-3xl border border-purple-300/70 bg-purple-50/80 p-8 shadow-sm dark:border-purple-300/30 dark:bg-purple-300/10 sm:p-10">
                            <x-heading type="h2" class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ __('public_features.show.api_reference.title') }}
                            </x-heading>
                            <x-paragraph class="mt-4 text-base leading-8 text-slate-700 dark:text-slate-200">
                                {{ __('public_features.show.api_reference.text') }}
                            </x-paragraph>
                            <x-primary-button :href="route('scribe')"
                                class="mt-6 bg-purple-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-purple-500/20 transition hover:bg-purple-600 focus:ring-purple-500 dark:bg-purple-400 dark:text-slate-950 dark:hover:bg-purple-300 dark:focus:ring-purple-300">
                                {{ __('public_features.common.api_docs') }}
                            </x-primary-button>
                        </section>
                    @endif

                    <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                        <x-heading type="h2" class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ __('public_features.show.related') }}
                        </x-heading>
                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            @foreach ($feature['related'] as $relatedSlug)
                                @if ($relatedSlug === 'monitoring-locations')
                                    <a href="{{ route('monitoring-locations') }}"
                                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-purple-300 dark:border-slate-800 dark:bg-slate-950/60 dark:hover:border-purple-400/50">
                                        <x-heading type="h3" class="text-base font-semibold text-slate-900 dark:text-white">
                                            {{ __('monitoring_locations.footer_link') }}
                                        </x-heading>
                                        <x-paragraph class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            {{ __('public_features.show.monitoring_locations_text') }}
                                        </x-paragraph>
                                    </a>
                                @else
                                    @php
                                        $relatedFeature = $features[$relatedSlug];
                                    @endphp
                                    <a href="{{ route('public-features.show', $relatedSlug) }}"
                                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-purple-300 dark:border-slate-800 dark:bg-slate-950/60 dark:hover:border-purple-400/50">
                                        <x-heading type="h3" class="text-base font-semibold text-slate-900 dark:text-white">
                                            {{ __('public_features.features.' . $relatedFeature['key'] . '.title') }}
                                        </x-heading>
                                        <x-paragraph class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            {{ __('public_features.features.' . $relatedFeature['key'] . '.teaser') }}
                                        </x-paragraph>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </section>
                </div>

                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                        <x-heading type="h2" class="text-lg font-semibold text-slate-900 dark:text-white">
                            {{ __('public_features.show.all_features') }}
                        </x-heading>
                        <nav class="mt-4 space-y-2" aria-label="{{ __('public_features.show.all_features') }}">
                            @foreach ($features as $otherSlug => $otherFeature)
                                <a href="{{ route('public-features.show', $otherSlug) }}"
                                    @class([
                                        'block rounded-lg px-3 py-2 text-sm font-medium transition',
                                        'bg-purple-100 text-purple-800 dark:bg-purple-300/15 dark:text-purple-200' => $otherSlug === $slug,
                                        'text-slate-700 hover:bg-slate-100 hover:text-purple-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-purple-300' => $otherSlug !== $slug,
                                    ])>
                                    {{ __('public_features.features.' . $otherFeature['key'] . '.title') }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            </x-main>
        </section>
    </main>
</x-marketing-layout>
