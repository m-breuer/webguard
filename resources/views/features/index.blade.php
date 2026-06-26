@php
    $featureItems = collect($features);
@endphp

<x-marketing-layout>
    <x-slot:title>{{ __('public_features.index.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('public_features.index.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('public_features.index.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('public_features.index.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('public_features.index.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:canonical>{{ route('public-features.index') }}</x-slot:canonical>

    <x-slot:head>
        @php
            $structuredData = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => __('public_features.index.hero.title'),
                'itemListElement' => $featureItems->keys()->values()->map(fn (string $slug, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('public-features.show', $slug),
                    'name' => __('public_features.features.' . $features[$slug]['key'] . '.title'),
                ])->all(),
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:head>

    <main>
        <header class="border-b border-slate-200/80 dark:border-slate-800/70">
            <x-main class="grid w-full gap-10 py-14 lg:grid-cols-[1fr_0.72fr] lg:items-center lg:py-20">
                <div>
                    <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">
                        {{ __('public_features.index.hero.eyebrow') }}
                    </x-paragraph>
                    <x-heading type="h1" class="mt-4 max-w-4xl text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                        {{ __('public_features.index.hero.title') }}
                    </x-heading>
                    <x-paragraph class="mt-5 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        {{ __('public_features.index.hero.subtitle') }}
                    </x-paragraph>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-primary-button :href="route('register')"
                            class="bg-purple-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-purple-500/20 transition hover:bg-purple-600 focus:ring-purple-500 dark:bg-purple-400 dark:text-slate-950 dark:hover:bg-purple-300 dark:focus:ring-purple-300">
                            {{ __('public_features.common.get_started') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['mode' => 'demo'])"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800">
                            {{ __('public_features.common.demo') }}
                        </x-secondary-button>
                        <x-secondary-button :href="route('scribe')"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800">
                            {{ __('public_features.common.api_docs') }}
                        </x-secondary-button>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-lg shadow-slate-300/20 dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-slate-950/20">
                    <x-paragraph class="text-sm font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">
                        {{ __('public_features.index.hero.summary_label') }}
                    </x-paragraph>
                    <dl class="mt-5 grid grid-cols-2 gap-3">
                        @foreach ([1, 2, 3, 4] as $metric)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">
                                    {{ __('public_features.index.hero.metrics.' . $metric . '.label') }}
                                </dt>
                                <dd class="mt-2 text-base font-semibold text-slate-900 dark:text-white">
                                    {{ __('public_features.index.hero.metrics.' . $metric . '.value') }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </x-main>
        </header>

        <section class="py-14 lg:py-20">
            <x-main class="space-y-14">
                @foreach ($categories as $category)
                    @php
                        $categoryFeatures = $featureItems->filter(fn (array $feature): bool => $feature['category'] === $category);
                    @endphp

                    <section aria-labelledby="feature-category-{{ $category }}">
                        <div class="max-w-3xl">
                            <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">
                                {{ __('public_features.categories.' . $category . '.eyebrow') }}
                            </x-paragraph>
                            <x-heading id="feature-category-{{ $category }}" type="h2" class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ __('public_features.categories.' . $category . '.title') }}
                            </x-heading>
                            <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">
                                {{ __('public_features.categories.' . $category . '.text') }}
                            </x-paragraph>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($categoryFeatures as $slug => $feature)
                                <a href="{{ route('public-features.show', $slug) }}"
                                    class="group rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-lg hover:shadow-slate-300/20 dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-purple-400/50 dark:hover:shadow-slate-950/20">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-300/15 dark:text-purple-300">
                                            @include('features.partials.icon', ['featureKey' => $feature['key']])
                                        </span>
                                        <span class="rounded-full border border-purple-300/70 bg-purple-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.08em] text-purple-700 dark:border-purple-300/30 dark:bg-purple-300/10 dark:text-purple-300">
                                            {{ __('public_features.features.' . $feature['key'] . '.badge') }}
                                        </span>
                                    </div>
                                    <x-heading type="h3" class="mt-5 text-xl font-semibold text-slate-900 dark:text-white">
                                        {{ __('public_features.features.' . $feature['key'] . '.title') }}
                                    </x-heading>
                                    <x-paragraph class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                        {{ __('public_features.features.' . $feature['key'] . '.teaser') }}
                                    </x-paragraph>
                                    <span class="mt-5 inline-flex items-center text-sm font-semibold text-purple-700 transition group-hover:text-purple-800 dark:text-purple-300 dark:group-hover:text-purple-200">
                                        {{ __('public_features.common.learn_more') }}
                                        <span class="ml-2" aria-hidden="true">-&gt;</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </x-main>
        </section>
    </main>
</x-marketing-layout>
