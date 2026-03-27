<x-marketing-layout>
    <x-slot:title>{{ __('legal.terms_of_use.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('legal.terms_of_use.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('legal.terms_of_use.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('legal.terms_of_use.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('legal.terms_of_use.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:robots>noindex, nofollow</x-slot:robots>
    <x-slot:canonical>{{ route('terms-of-use') }}</x-slot:canonical>

    <main class="py-14 lg:py-20">
        <x-main class="w-full space-y-8 lg:space-y-10">
            <header class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-emerald-700 dark:text-emerald-300">
                    {{ __('legal.terms_of_use.hero.eyebrow') }}
                </x-paragraph>
                <x-heading type="h1" class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    {{ __('legal.terms_of_use.hero.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-300">
                    {{ __('legal.terms_of_use.hero.subtitle') }}
                </x-paragraph>
            </header>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.scope.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.scope.intro') }}
                </x-paragraph>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach (trans('legal.terms_of_use.sections.scope.items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.obligations.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.obligations.intro') }}
                </x-paragraph>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach (trans('legal.terms_of_use.sections.obligations.items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.liability.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.liability.body') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.termination.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.termination.body') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.governing_law.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.governing_law.body') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.contact.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.contact.body') }}
                </x-paragraph>

                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('imprint.contact_hint') }}
                </x-paragraph>

                <div class="mt-4">
                    <x-primary-button type="button" data-imprint-reveal data-email-payload="{{ $email_payload }}"
                        data-phone-payload="{{ $phone_payload }}"
                        class="bg-emerald-500 text-white normal-case tracking-normal hover:bg-emerald-600 focus:ring-emerald-500 dark:bg-emerald-400 dark:text-slate-950 dark:hover:bg-emerald-300 dark:focus:ring-emerald-300">
                        {{ __('imprint.actions.reveal_contact') }}
                    </x-primary-button>
                </div>

                <dl class="mt-4 space-y-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('imprint.fields.email') }}</dt>
                        <dd id="imprint-email">{{ __('imprint.contact_hidden') }}</dd>
                    </div>

                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('imprint.fields.phone') }}</dt>
                        <dd id="imprint-phone">{{ __('imprint.contact_hidden') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-amber-200 bg-amber-50/80 p-8 dark:border-amber-900/50 dark:bg-amber-950/20 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('legal.terms_of_use.sections.non_commercial.title') }}
                </x-heading>
                <x-paragraph class="mt-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('legal.terms_of_use.sections.non_commercial.body') }}
                </x-paragraph>
            </section>
        </x-main>
    </main>
</x-marketing-layout>
