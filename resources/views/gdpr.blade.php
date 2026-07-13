@php
    $dataCategories = (array) trans('gdpr.sections.data_categories.items');
    $processingPurposes = (array) trans('gdpr.sections.purposes_legal_basis.purposes');
    $legalBases = (array) trans('gdpr.sections.purposes_legal_basis.legal_basis');
    $thirdPartyServices = (array) trans('gdpr.sections.third_party.items');
    $cookieItems = (array) trans('gdpr.sections.cookies.items');
    $userRights = (array) trans('gdpr.sections.rights.items');
@endphp

<x-legal-layout>
    <x-slot:title>{{ __('gdpr.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('gdpr.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('gdpr.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('gdpr.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('gdpr.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:canonical>{{ route('gdpr') }}</x-slot:canonical>

    <main class="py-14 lg:py-20">
        <x-main class="w-full space-y-8 lg:space-y-10">
            <header class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-purple-700 dark:text-purple-300">
                    {{ __('gdpr.hero.eyebrow') }}
                </x-paragraph>
                <x-heading type="h1" class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    {{ __('gdpr.hero.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-300">
                    {{ __('gdpr.hero.subtitle') }}
                </x-paragraph>
                <x-paragraph class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('gdpr.hero.last_updated', ['date' => __('gdpr.hero.last_updated_date')]) }}
                </x-paragraph>
            </header>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.controller.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.controller.lead') }}
                </x-paragraph>

                <dl class="mt-4 space-y-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('gdpr.fields.operator_name') }}</dt>
                        <dd>{{ $imprint['operator_name'] }}</dd>
                    </div>

                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('gdpr.fields.address') }}</dt>
                        <dd>
                            {{ $imprint['street'] }}<br>
                            {{ $imprint['postal_code'] }} {{ $imprint['city'] }}<br>
                            {{ $imprint['country'] }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.data_categories.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.data_categories.lead') }}
                </x-paragraph>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ($dataCategories as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.purposes_legal_basis.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.purposes_legal_basis.lead') }}
                </x-paragraph>

                <x-heading type="h3" class="mt-6 text-lg font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.purposes_legal_basis.purposes_title') }}
                </x-heading>
                <ul class="mt-3 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ($processingPurposes as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <x-heading type="h3" class="mt-6 text-lg font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.purposes_legal_basis.legal_basis_title') }}
                </x-heading>
                <ul class="mt-3 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ($legalBases as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.third_party.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.third_party.lead') }}
                </x-paragraph>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ($thirdPartyServices as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.third_party.note') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.cookies.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.cookies.lead') }}
                </x-paragraph>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ($cookieItems as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.cookies.options') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.rights.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.rights.lead') }}
                </x-paragraph>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    @foreach ($userRights as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.retention.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.retention.lead') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.security.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.security.lead') }}
                </x-paragraph>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('gdpr.sections.contact.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.contact.lead') }}
                </x-paragraph>

                <x-paragraph class="mt-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('imprint.contact_hint') }}
                </x-paragraph>

                <div class="mt-4">
                    <x-primary-button type="button" data-imprint-reveal data-email-payload="{{ $email_payload }}"
                        data-phone-payload="{{ $phone_payload }}"
                        class="bg-purple-500 text-white normal-case tracking-normal hover:bg-purple-600 focus:ring-purple-500 dark:bg-purple-400 dark:text-slate-950 dark:hover:bg-purple-300 dark:focus:ring-purple-300">
                        {{ __('imprint.actions.reveal_contact') }}
                    </x-primary-button>
                </div>

                <dl class="mt-4 space-y-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('gdpr.fields.email') }}</dt>
                        <dd id="imprint-email">{{ __('imprint.contact_hidden') }}</dd>
                    </div>

                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('gdpr.fields.phone') }}</dt>
                        <dd id="imprint-phone">{{ __('imprint.contact_hidden') }}</dd>
                    </div>
                </dl>

                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('gdpr.sections.contact.complaint') }}
                </x-paragraph>
            </section>
        </x-main>
    </main>
</x-legal-layout>
