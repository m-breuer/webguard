@php
    $features = \App\Http\Controllers\PublicFeatureController::features();
    $monitoringInterval = (int) config('monitoring.interval', 5);
    $monitoringIntervalCopy = trans_choice('welcome.case_study.metrics.2.value', $monitoringInterval, ['count' => $monitoringInterval]);
    $primaryFeatureSlugs = [
        'http-monitoring',
        'ping-monitoring',
        'heartbeat-monitoring',
        'server-health-monitoring',
        'dns-record-monitoring',
        'public-labels',
    ];
@endphp

<x-marketing-layout>
    <x-slot:title>{{ __('welcome.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('welcome.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('welcome.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('welcome.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('welcome.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:ogImage>{{ Vite::asset('resources/images/landing-dashboard.svg') }}</x-slot:ogImage>
    <x-slot:twitterImage>{{ Vite::asset('resources/images/landing-dashboard.svg') }}</x-slot:twitterImage>
    <x-slot:canonical>{{ route('welcome') }}</x-slot:canonical>

    <x-slot:head>
        @php
            $structuredData = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => __('app.name'),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'description' => __('welcome.seo.description'),
                'url' => route('welcome'),
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'EUR',
                ],
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:head>

    <main>
        <header class="border-b border-slate-200/80 dark:border-slate-800/70">
            <x-main class="grid w-full gap-12 py-14 lg:grid-cols-[1fr_0.9fr] lg:items-center lg:py-20">
                <div>
                    <x-paragraph class="inline-flex items-center rounded-full border border-emerald-400/50 bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-300/10 dark:text-emerald-300">
                        {{ __('welcome.hero.eyebrow') }}
                    </x-paragraph>
                    <x-heading type="h1" class="mt-6 max-w-4xl text-4xl font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl">
                        {{ __('welcome.hero.title') }}
                    </x-heading>
                    <x-paragraph class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300 sm:text-xl">
                        {{ __('welcome.hero.subtitle') }}
                    </x-paragraph>

                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        <x-primary-button :href="route('register')"
                            class="bg-emerald-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600 focus:ring-emerald-500 dark:bg-emerald-400 dark:text-slate-950 dark:hover:bg-emerald-300 dark:focus:ring-emerald-300">
                            {{ __('welcome.hero.primary_cta') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['mode' => 'demo'])"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800">
                            {{ __('welcome.hero.secondary_cta') }}
                        </x-secondary-button>
                    </div>
                </div>

                <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-2 shadow-2xl shadow-slate-300/30 dark:border-slate-700/70 dark:bg-slate-900/80 dark:shadow-slate-950/60">
                    <img src="{{ Vite::asset('resources/images/landing-dashboard.svg') }}"
                        alt="{{ __('welcome.visuals.previews.dashboard.alt') }}"
                        width="1280"
                        height="780"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                        class="h-auto w-full rounded-xl">
                </figure>
            </x-main>
        </header>

        <section class="border-b border-slate-200/80 bg-white/70 py-8 dark:border-slate-800/70 dark:bg-slate-950/30">
            <x-main>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ([1, 2, 3] as $metric)
                        @php
                            $metricValue = __('welcome.hero.metrics.' . $metric . '.value', ['interval' => $monitoringIntervalCopy]);
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('welcome.hero.metrics.' . $metric . '.label') }}</dt>
                            <dd class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $metricValue }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-main>
        </section>

        <section id="features" class="py-16 lg:py-20">
            <x-main>
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-emerald-700 dark:text-emerald-300">{{ __('welcome.feature_section.eyebrow') }}</x-paragraph>
                        <x-heading type="h2" class="mt-4 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.feature_section.title') }}</x-heading>
                        <x-paragraph class="mt-4 max-w-3xl text-lg text-slate-600 dark:text-slate-300">{{ __('welcome.feature_section.subtitle') }}</x-paragraph>
                    </div>
                    <x-secondary-button :href="route('public-features.index')"
                        class="border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800">
                        {{ __('welcome.feature_section.all_features_cta') }}
                    </x-secondary-button>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($primaryFeatureSlugs as $slug)
                        @php
                            $feature = $features[$slug];
                        @endphp
                        <a href="{{ route('public-features.show', $slug) }}"
                            class="group rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-slate-300/20 dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-emerald-400/50 dark:hover:shadow-slate-950/20">
                            <div class="flex items-center justify-between gap-4">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-300/15 dark:text-emerald-300">
                                    @include('features.partials.icon', ['featureKey' => $feature['key']])
                                </span>
                                <span class="rounded-full border border-emerald-300/70 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.08em] text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-300/10 dark:text-emerald-300">
                                    {{ __('public_features.features.' . $feature['key'] . '.badge') }}
                                </span>
                            </div>
                            <x-heading type="h3" class="mt-5 text-xl font-semibold text-slate-900 dark:text-white">{{ __('public_features.features.' . $feature['key'] . '.title') }}</x-heading>
                            <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('public_features.features.' . $feature['key'] . '.teaser') }}</x-paragraph>
                            <span class="mt-5 inline-flex items-center text-sm font-semibold text-emerald-700 transition group-hover:text-emerald-800 dark:text-emerald-300 dark:group-hover:text-emerald-200">
                                {{ __('public_features.common.learn_more') }}
                                <span class="ml-2" aria-hidden="true">-&gt;</span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="mt-10 rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/70">
                    <x-heading type="h3" class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.feature_section.full_stack_title') }}</x-heading>
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($features as $slug => $feature)
                            <a href="{{ route('public-features.show', $slug) }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-emerald-300 dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-emerald-400/50">
                                <x-heading type="h4" class="text-base font-semibold text-slate-900 dark:text-white">{{ __('public_features.features.' . $feature['key'] . '.title') }}</x-heading>
                                <x-paragraph class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('public_features.features.' . $feature['key'] . '.teaser') }}</x-paragraph>
                            </a>
                        @endforeach
                    </div>
                </div>
            </x-main>
        </section>

        <section class="border-y border-slate-200/80 bg-slate-100/70 py-16 dark:border-slate-800/70 dark:bg-slate-900/40 lg:py-20">
            <x-main>
                <div class="max-w-3xl">
                    <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-sky-700 dark:text-sky-300">{{ __('welcome.platform.eyebrow') }}</x-paragraph>
                    <x-heading type="h2" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.platform.title') }}</x-heading>
                    <x-paragraph class="mt-4 text-lg text-slate-600 dark:text-slate-300">{{ __('welcome.platform.subtitle') }}</x-paragraph>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach (['public_status_pages', 'embeddable_widget', 'notifications', 'rest_api'] as $featureKey)
                        @php
                            $slug = collect($features)->search(fn (array $feature): bool => $feature['key'] === $featureKey);
                        @endphp
                        <a href="{{ route('public-features.show', $slug) }}"
                            class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition hover:border-emerald-300 dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-emerald-400/50">
                            <x-heading type="h3" class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('public_features.features.' . $featureKey . '.title') }}</x-heading>
                            <x-paragraph class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ __('public_features.features.' . $featureKey . '.teaser') }}</x-paragraph>
                        </a>
                    @endforeach
                </div>
            </x-main>
        </section>

        <section id="proof" class="py-16 lg:py-20">
            <x-main>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/70">
                        <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.workflow.steps.1.title') }}</x-heading>
                        <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.steps.1.text') }}</x-paragraph>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/70">
                        <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.workflow.steps.2.title') }}</x-heading>
                        <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.steps.2.text') }}</x-paragraph>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/70">
                        <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.workflow.steps.3.title') }}</x-heading>
                        <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.steps.3.text') }}</x-paragraph>
                    </article>
                </div>
            </x-main>
        </section>

        <section id="pricing-cta" class="pb-16 lg:pb-20">
            <x-main>
                <div class="rounded-3xl border border-emerald-400/40 bg-emerald-100/80 p-8 sm:p-10 lg:p-12 dark:border-emerald-300/30 dark:bg-emerald-300/10">
                    <x-heading type="h2" class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.final_cta.title') }}</x-heading>
                    <x-paragraph class="mt-4 max-w-2xl text-lg text-slate-700 dark:text-slate-200">{{ __('welcome.final_cta.text') }}</x-paragraph>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-primary-button :href="route('register')"
                            class="bg-emerald-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600 focus:ring-emerald-500 dark:bg-emerald-400 dark:text-slate-950 dark:hover:bg-emerald-300 dark:focus:ring-emerald-300">
                            {{ __('welcome.final_cta.primary') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['mode' => 'demo'])"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-950/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-900">
                            {{ __('welcome.final_cta.secondary') }}
                        </x-secondary-button>
                    </div>
                </div>
            </x-main>
        </section>
    </main>
</x-marketing-layout>
