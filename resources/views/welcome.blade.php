@php
    $features = \App\Http\Controllers\PublicFeatureController::features();
    $monitoringInterval = (int) config('monitoring.interval', 5);
    $monitoringIntervalCopy = trans_choice('welcome.case_study.metrics.2.value', $monitoringInterval, ['count' => $monitoringInterval]);
    $featureRoutes = [
        'website_api' => ['http-monitoring', 'keyword-monitoring', 'status-code-monitoring'],
        'infrastructure' => ['ping-monitoring', 'port-monitoring', 'dns-record-monitoring'],
        'automation' => ['heartbeat-monitoring', 'notifications', 'weekly-digest'],
        'status' => ['public-status-pages', 'public-labels', 'status-badges'],
        'integrations' => ['api', 'server-health-monitoring', 'monitoring-groups'],
    ];
@endphp

<x-marketing-layout>
    <x-slot:title>{{ __('welcome.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('welcome.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('welcome.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('welcome.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('welcome.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:ogImage>{{ Vite::asset('resources/images/landing-hero-purple.webp') }}</x-slot:ogImage>
    <x-slot:twitterImage>{{ Vite::asset('resources/images/landing-hero-purple.webp') }}</x-slot:twitterImage>
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
        <header class="border-b border-slate-200/80 bg-white dark:border-slate-800/70 dark:bg-slate-950">
            <x-main class="grid w-full gap-10 py-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:py-16">
                <div class="max-w-2xl">
                    <x-heading type="h1" class="max-w-4xl text-4xl font-extrabold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-5xl lg:text-5xl">
                        {{ __('welcome.hero.title') }}
                    </x-heading>
                    <x-paragraph class="mt-6 max-w-xl text-lg leading-8 text-slate-600 dark:text-slate-300 sm:text-xl">
                        {{ __('welcome.hero.subtitle') }}
                    </x-paragraph>

                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        <x-primary-button :href="route('register')"
                            class="!border-purple-600 !bg-purple-600 px-6 py-3 text-base font-semibold !text-white !normal-case !tracking-normal shadow-lg shadow-purple-600/20 transition hover:!bg-purple-700 focus:!ring-purple-500 dark:!border-purple-400 dark:!bg-purple-400 dark:!text-slate-950 dark:hover:!bg-purple-300 dark:focus:!ring-purple-300">
                            {{ __('welcome.hero.primary_cta') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['mode' => 'demo'])"
                            class="!border-slate-300 !bg-white px-6 py-3 text-base font-semibold !text-slate-700 !normal-case !tracking-normal transition hover:!border-slate-400 hover:!bg-slate-100 dark:!border-slate-600 dark:!bg-slate-900/70 dark:!text-slate-100 dark:hover:!border-slate-500 dark:hover:!bg-slate-800">
                            {{ __('welcome.hero.secondary_cta') }}
                        </x-secondary-button>
                    </div>

                </div>

                <figure class="lg:pt-2">
                    <div class="overflow-hidden rounded-2xl border border-purple-100 bg-white shadow-2xl shadow-purple-200/30 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/60">
                        <img src="{{ Vite::asset('resources/images/landing-hero-purple.webp') }}"
                            alt="{{ __('welcome.visuals.photos.hero_alt') }}"
                            width="1672"
                            height="941"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            class="aspect-[16/9] h-auto w-full object-cover object-bottom">
                    </div>
                </figure>

                <dl class="grid max-w-xl grid-cols-1 gap-4 sm:grid-cols-3 lg:col-start-1 lg:row-start-2">
                    @foreach ([1, 2, 3] as $metric)
                        @php
                            $metricValue = __('welcome.hero.metrics.' . $metric . '.value', ['interval' => $monitoringIntervalCopy]);
                        @endphp
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('welcome.hero.metrics.' . $metric . '.label') }}</dt>
                            <dd class="mt-2 text-sm font-semibold leading-6 text-slate-900 dark:text-slate-100">{{ $metricValue }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-main>
        </header>

        <section id="features" class="border-y border-slate-200/80 bg-purple-50/40 py-14 dark:border-slate-800/70 dark:bg-slate-900/40 lg:py-18">
            <x-main>
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">{{ __('welcome.feature_section.eyebrow') }}</x-paragraph>
                        <x-heading type="h2" class="mt-4 max-w-3xl text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">{{ __('welcome.feature_section.title') }}</x-heading>
                        <x-paragraph class="mt-4 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">{{ __('welcome.feature_section.subtitle') }}</x-paragraph>
                    </div>
                    <x-secondary-button :href="route('public-features.index')"
                        class="!border-slate-300 !bg-white px-5 py-3 text-sm font-semibold !text-slate-700 !normal-case !tracking-normal transition hover:!border-purple-300 hover:!bg-purple-50 hover:!text-purple-700 dark:!border-slate-600 dark:!bg-slate-900/70 dark:!text-slate-100 dark:hover:!border-purple-500 dark:hover:!bg-slate-800">
                            {{ __('welcome.feature_section.all_features_cta') }}
                    </x-secondary-button>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    @foreach ($featureRoutes as $routeKey => $routeSlugs)
                        @php
                            $primarySlug = $routeSlugs[0];
                            $primaryFeature = $features[$primarySlug];
                        @endphp
                        <article
                            class="group flex min-h-64 flex-col rounded-xl border border-purple-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-lg hover:shadow-purple-100/80 dark:border-slate-800 dark:bg-slate-950/70 dark:hover:border-purple-400/50 dark:hover:shadow-slate-950/30">
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-50 text-purple-700 dark:bg-purple-300/10 dark:text-purple-300">
                                @include('features.partials.icon', ['featureKey' => $primaryFeature['key']])
                            </span>
                            <x-heading type="h3" class="mt-5 text-lg font-semibold leading-7 text-slate-950 dark:text-white">
                                <a href="{{ route('public-features.show', $primarySlug) }}" class="transition hover:text-purple-700 dark:hover:text-purple-300">
                                    {{ __('welcome.feature_routes.' . $routeKey . '.title') }}
                                </a>
                            </x-heading>
                            <x-paragraph class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('welcome.feature_routes.' . $routeKey . '.text') }}</x-paragraph>
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach ($routeSlugs as $routeSlug)
                                    @php
                                        $routeFeature = $features[$routeSlug];
                                    @endphp
                                    <a href="{{ route('public-features.show', $routeSlug) }}"
                                        class="rounded-full border border-purple-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-purple-400/50 dark:hover:bg-purple-300/10 dark:hover:text-purple-200">
                                        {{ __('public_features.features.' . $routeFeature['key'] . '.title') }}
                                    </a>
                                @endforeach
                            </div>
                            <a href="{{ route('public-features.show', $primarySlug) }}" class="mt-auto pt-5 text-sm font-semibold text-purple-700 dark:text-purple-300">
                                {{ __('welcome.feature_routes.' . $routeKey . '.cta') }}
                                <span aria-hidden="true">-&gt;</span>
                            </a>
                        </article>
                    @endforeach
                </div>
            </x-main>
        </section>

        <section class="bg-white py-16 dark:bg-slate-950 lg:py-20">
            <x-main>
                <div class="grid gap-10 lg:grid-cols-[1fr_0.9fr] lg:items-center">
                    <div>
                        <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">{{ __('welcome.platform.eyebrow') }}</x-paragraph>
                        <x-heading type="h2" class="mt-4 max-w-3xl text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">{{ __('welcome.platform.title') }}</x-heading>
                        <x-paragraph class="mt-4 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">{{ __('welcome.platform.subtitle') }}</x-paragraph>

                        <div class="mt-8 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-950/60">
                            @foreach (['public_status_pages', 'sla_badge', 'notifications', 'rest_api'] as $featureKey)
                                @php
                                    $slug = collect($features)->search(fn (array $feature): bool => $feature['key'] === $featureKey);
                                @endphp
                                <a href="{{ route('public-features.show', $slug) }}" class="block p-5 transition hover:bg-purple-50/70 dark:hover:bg-purple-300/5">
                                    <x-heading type="h3" class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('public_features.features.' . $featureKey . '.title') }}</x-heading>
                                    <x-paragraph class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ __('public_features.features.' . $featureKey . '.teaser') }}</x-paragraph>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-300/25 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/50">
                        <img src="{{ Vite::asset('resources/images/landing-status-purple.webp') }}"
                            alt="{{ __('welcome.visuals.photos.status_alt') }}"
                            width="1448"
                            height="1086"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[4/3] h-auto w-full object-cover">
                    </figure>
                </div>
            </x-main>
        </section>

        <section id="proof" class="border-y border-slate-200/80 bg-slate-50 py-16 dark:border-slate-800/70 dark:bg-slate-900/40 lg:py-20">
            <x-main>
                <div class="grid gap-10 lg:grid-cols-[0.94fr_1.06fr] lg:items-center">
                    <figure class="order-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-300/25 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/50 lg:order-1">
                        <img src="{{ Vite::asset('resources/images/landing-integrations-purple.webp') }}"
                            alt="{{ __('welcome.visuals.photos.workflow_alt') }}"
                            width="1448"
                            height="1086"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[4/3] h-auto w-full object-cover">
                    </figure>

                    <div class="order-1 lg:order-2">
                        <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">{{ __('welcome.workflow.eyebrow') }}</x-paragraph>
                        <x-heading type="h2" class="mt-4 max-w-3xl text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">{{ __('welcome.workflow.title') }}</x-heading>
                        <x-paragraph class="mt-4 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.subtitle') }}</x-paragraph>

                        <div class="mt-8 space-y-5">
                            @foreach ([1, 2, 3] as $step)
                                <article class="flex gap-4">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-purple-300 bg-purple-50 text-sm font-bold text-purple-700 dark:border-purple-300/40 dark:bg-purple-300/10 dark:text-purple-300">{{ $step }}</span>
                                    <span>
                                        <x-heading type="h3" class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('welcome.workflow.steps.' . $step . '.title') }}</x-heading>
                                        <x-paragraph class="mt-2 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.steps.' . $step . '.text') }}</x-paragraph>
                                    </span>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-main>
        </section>

        <section id="pricing-cta" class="border-t border-slate-200/80 bg-slate-50 py-16 dark:border-slate-800/70 dark:bg-slate-900/40 lg:py-20">
            <x-main>
                <div class="grid gap-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-950/70 dark:shadow-slate-950/40 lg:grid-cols-[1fr_auto] lg:items-center lg:p-10">
                    <div>
                        <x-heading type="h2" class="max-w-3xl text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">{{ __('welcome.final_cta.title') }}</x-heading>
                        <x-paragraph class="mt-4 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">{{ __('welcome.final_cta.text') }}</x-paragraph>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button :href="route('register')"
                            class="!border-purple-600 !bg-purple-600 px-6 py-3 text-base font-semibold !text-white !normal-case !tracking-normal shadow-lg shadow-purple-600/20 transition hover:!bg-purple-700 focus:!ring-purple-500 dark:!border-purple-400 dark:!bg-purple-400 dark:!text-slate-950 dark:hover:!bg-purple-300 dark:focus:!ring-purple-300">
                            {{ __('welcome.final_cta.primary') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['mode' => 'demo'])"
                            class="!border-slate-300 !bg-white px-6 py-3 text-base font-semibold !text-slate-700 !normal-case !tracking-normal transition hover:!border-slate-400 hover:!bg-slate-100 dark:!border-slate-600 dark:!bg-slate-950/70 dark:!text-slate-100 dark:hover:!border-slate-500 dark:hover:!bg-slate-900">
                            {{ __('welcome.final_cta.secondary') }}
                        </x-secondary-button>
                    </div>
                </div>
            </x-main>
        </section>
    </main>
</x-marketing-layout>
