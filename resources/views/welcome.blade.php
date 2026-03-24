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
        <header class="relative border-b border-slate-200/80 dark:border-slate-800/70">
            <x-main class="grid w-full gap-12 pb-16 pt-14 lg:grid-cols-2 lg:items-center lg:gap-16 lg:pb-24 lg:pt-20">
                <div>
                    <x-paragraph class="inline-flex items-center rounded-full border border-emerald-400/50 bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-300/10 dark:text-emerald-300">
                        {{ __('welcome.hero.eyebrow') }}
                    </x-paragraph>
                    <x-heading type="h1" class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl">
                        {{ __('welcome.hero.title') }}
                    </x-heading>
                    <x-paragraph class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300 sm:text-xl">
                        {{ __('welcome.hero.subtitle') }}
                    </x-paragraph>

                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        <x-primary-button :href="route('login')"
                            class="bg-emerald-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600 focus:ring-emerald-500 dark:bg-emerald-400 dark:text-slate-950 dark:hover:bg-emerald-300 dark:focus:ring-emerald-300">
                            {{ __('button.login') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['guest' => 'true'])"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800">
                            {{ __('welcome.hero.secondary_cta') }}
                        </x-secondary-button>
                    </div>

                    <dl class="mt-10 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach ([1, 2, 3] as $metric)
                            <div class="rounded-xl border border-slate-200 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('welcome.hero.metrics.' . $metric . '.label') }}</dt>
                                <dd class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('welcome.hero.metrics.' . $metric . '.value') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="relative">
                    <div class="absolute -left-4 -top-4 h-28 w-28 rounded-full bg-sky-400/20 blur-2xl dark:bg-sky-300/20"></div>
                    <div class="absolute -bottom-6 right-0 h-36 w-36 rounded-full bg-emerald-400/20 blur-2xl dark:bg-emerald-300/20"></div>
                    <figure class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-2 shadow-2xl shadow-slate-300/30 dark:border-slate-700/70 dark:bg-slate-900/80 dark:shadow-slate-950/60">
                        <img src="{{ Vite::asset('resources/images/landing-dashboard.svg') }}"
                            alt="{{ __('welcome.visuals.previews.dashboard.alt') }}"
                            width="1280"
                            height="780"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            class="h-auto w-full rounded-xl">
                    </figure>
                </div>
            </x-main>
        </header>

        <section id="features" class="py-16 lg:py-20">
            <x-main>
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-emerald-700 dark:text-emerald-300">{{ __('welcome.feature_section.eyebrow') }}</x-paragraph>
                <x-heading type="h2" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.feature_section.title') }}</x-heading>
                <x-paragraph class="mt-4 max-w-3xl text-lg text-slate-600 dark:text-slate-300">{{ __('welcome.feature_section.subtitle') }}</x-paragraph>

                <div class="mt-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach (['http', 'ping', 'keyword', 'port', 'notifications', 'ssl', 'stats', 'multi_location'] as $feature)
                        <article class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-lg shadow-slate-300/20 dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-slate-950/20">
                            <div class="flex items-center justify-between gap-4">
                                <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-emerald-700 dark:bg-slate-800 dark:text-emerald-300">
                                    @switch($feature)
                                        @case('http')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M3 12h18M3 19h18" /></svg>
                                        @break

                                        @case('ping')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h4l3-6 4 12 2-6h3" /></svg>
                                        @break

                                        @case('keyword')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6h10M4 12h16M8 18h12" /></svg>
                                        @break

                                        @case('port')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10v8H7z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 8V6m6 2V6m-6 10v2m6-2v2" /></svg>
                                        @break

                                        @case('notifications')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" /></svg>
                                        @break

                                        @case('ssl')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V8a4 4 0 118 0v3m-9 0h10v9H7z" /></svg>
                                        @break

                                        @case('stats')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5v14h14M9 15l3-3 2 2 4-5" /></svg>
                                        @break

                                        @case('multi_location')
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 100 18 9 9 0 000-18z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3c2.5 2.4 2.5 15.6 0 18" /></svg>
                                        @break
                                    @endswitch
                                </div>

                                <x-badge type="success" class="border border-emerald-400/50 bg-emerald-100 px-3 py-1 text-xs uppercase tracking-[0.08em] text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-300/10 dark:text-emerald-300">
                                    {{ __('welcome.features.' . $feature . '.badge') }}
                                </x-badge>
                            </div>

                            <x-heading type="h3" class="mt-5 text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.features.' . $feature . '.title') }}</x-heading>
                            <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.features.' . $feature . '.text') }}</x-paragraph>
                        </article>
                    @endforeach
                </div>
            </x-main>
        </section>

        <section class="border-y border-slate-200/80 bg-slate-100/70 dark:border-slate-800/70 dark:bg-slate-900/40">
            <x-main class="py-16 lg:py-20">
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-sky-700 dark:text-sky-300">{{ __('welcome.visuals.eyebrow') }}</x-paragraph>
                <x-heading type="h2" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.visuals.title') }}</x-heading>
                <x-paragraph class="mt-4 max-w-3xl text-lg text-slate-600 dark:text-slate-300">{{ __('welcome.visuals.subtitle') }}</x-paragraph>

                <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    @foreach ([
                        'dashboard' => 'resources/images/landing-dashboard.svg',
                        'detail' => 'resources/images/landing-monitoring-detail.svg',
                        'public_status' => 'resources/images/landing-public-status.svg',
                    ] as $preview => $asset)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white/90 dark:border-slate-800 dark:bg-slate-900/70">
                            <img src="{{ Vite::asset($asset) }}"
                                alt="{{ __('welcome.visuals.previews.' . $preview . '.alt') }}"
                                width="1280"
                                height="780"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                decoding="async"
                                class="h-auto w-full border-b border-slate-200 dark:border-slate-800">
                            <div class="p-5">
                                <x-heading type="h3" class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('welcome.visuals.previews.' . $preview . '.title') }}</x-heading>
                                <x-paragraph class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.visuals.previews.' . $preview . '.text') }}</x-paragraph>
                            </div>
                        </article>
                    @endforeach
                </div>
            </x-main>
        </section>

        <section class="py-16 lg:py-20">
            <x-main>
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-emerald-700 dark:text-emerald-300">{{ __('welcome.workflow.eyebrow') }}</x-paragraph>
                <x-heading type="h2" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.workflow.title') }}</x-heading>
                <x-paragraph class="mt-4 max-w-3xl text-lg text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.subtitle') }}</x-paragraph>

                <ol class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ([1, 2, 3] as $step)
                        <li class="rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/70">
                            <x-span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700 dark:bg-emerald-300/15 dark:text-emerald-300">{{ $step }}</x-span>
                            <x-heading type="h3" class="mt-4 text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.workflow.steps.' . $step . '.title') }}</x-heading>
                            <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.workflow.steps.' . $step . '.text') }}</x-paragraph>
                        </li>
                    @endforeach
                </ol>
            </x-main>
        </section>

        <section id="proof" class="border-y border-slate-200/80 bg-slate-100/70 dark:border-slate-800/70 dark:bg-slate-900/40">
            <x-main class="py-16 lg:py-20">
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-sky-700 dark:text-sky-300">{{ __('welcome.trust.eyebrow') }}</x-paragraph>
                <x-heading type="h2" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.trust.title') }}</x-heading>
                <x-paragraph class="mt-4 max-w-3xl text-lg text-slate-600 dark:text-slate-300">{{ __('welcome.trust.subtitle') }}</x-paragraph>

                <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <blockquote class="rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-950/70 lg:col-span-1">
                        <x-paragraph class="text-lg leading-8 text-slate-800 dark:text-slate-100">“{{ __('welcome.testimonial.quote') }}”</x-paragraph>
                    </blockquote>

                    <article class="rounded-2xl border border-slate-200 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-950/70 lg:col-span-2">
                        <x-heading type="h3" class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('welcome.case_study.title') }}</x-heading>
                        <x-paragraph class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.case_study.text') }}</x-paragraph>
                        <dl class="mt-5 space-y-3 text-sm">
                            @foreach ([1, 2, 3] as $metric)
                                <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-800 dark:bg-slate-900/80">
                                    <dt><x-span class="text-slate-600 dark:text-slate-300">{{ __('welcome.case_study.metrics.' . $metric . '.label') }}</x-span></dt>
                                    <dd><x-span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ __('welcome.case_study.metrics.' . $metric . '.value') }}</x-span></dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach (['uptime', 'gdpr', 'transparent'] as $badge)
                        <article class="rounded-xl border border-slate-200 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/70">
                            <x-heading type="h3" class="text-sm font-semibold uppercase tracking-[0.08em] text-emerald-700 dark:text-emerald-300">{{ __('welcome.badges.' . $badge . '.title') }}</x-heading>
                            <x-paragraph class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ __('welcome.badges.' . $badge . '.text') }}</x-paragraph>
                        </article>
                    @endforeach
                </div>
            </x-main>
        </section>

        <section id="pricing-cta" class="py-16 lg:py-20">
            <x-main>
                <div class="rounded-3xl border border-emerald-400/40 bg-emerald-100/80 p-8 sm:p-10 lg:p-12 dark:border-emerald-300/30 dark:bg-emerald-300/10">
                    <x-heading type="h2" class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('welcome.final_cta.title') }}</x-heading>
                    <x-paragraph class="mt-4 max-w-2xl text-lg text-slate-700 dark:text-slate-200">{{ __('welcome.final_cta.text') }}</x-paragraph>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-primary-button :href="route('register')"
                            class="bg-emerald-500 px-6 py-3 text-base font-semibold text-white normal-case tracking-normal shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600 focus:ring-emerald-500 dark:bg-emerald-400 dark:text-slate-950 dark:hover:bg-emerald-300 dark:focus:ring-emerald-300">
                            {{ __('welcome.final_cta.primary') }}
                        </x-primary-button>
                        <x-secondary-button :href="route('login', ['guest' => 'true'])"
                            class="border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 normal-case tracking-normal transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-950/70 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-900">
                            {{ __('welcome.final_cta.secondary') }}
                        </x-secondary-button>
                    </div>
                </div>
            </x-main>
        </section>
    </main>
</x-marketing-layout>
