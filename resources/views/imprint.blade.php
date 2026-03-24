<x-marketing-layout>
    <x-slot:title>{{ __('imprint.seo.title') }}</x-slot:title>
    <x-slot:description>{{ __('imprint.seo.description') }}</x-slot:description>
    <x-slot:keywords>{{ __('imprint.seo.keywords') }}</x-slot:keywords>
    <x-slot:ogTitle>{{ __('imprint.seo.og_title') }}</x-slot:ogTitle>
    <x-slot:ogDescription>{{ __('imprint.seo.og_description') }}</x-slot:ogDescription>
    <x-slot:robots>noindex, nofollow</x-slot:robots>
    <x-slot:canonical>{{ route('imprint') }}</x-slot:canonical>

    <main class="py-14 lg:py-20">
        <x-main class="w-full space-y-8 lg:space-y-10">
            <header class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-paragraph class="text-sm font-semibold uppercase tracking-[0.1em] text-emerald-700 dark:text-emerald-300">
                    {{ __('imprint.hero.eyebrow') }}
                </x-paragraph>
                <x-heading type="h1" class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    {{ __('imprint.hero.title') }}
                </x-heading>
                <x-paragraph class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-300">
                    {{ __('imprint.hero.subtitle') }}
                </x-paragraph>
            </header>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('imprint.sections.operator') }}
                </x-heading>

                <dl class="mt-4 space-y-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                        <dt class="min-w-44 font-semibold text-slate-900 dark:text-slate-100">{{ __('imprint.fields.full_name') }}</dt>
                        <dd>{{ $imprint['operator_name'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('imprint.sections.address') }}
                </x-heading>

                <address class="mt-4 not-italic text-sm leading-7 text-slate-700 dark:text-slate-300">
                    <p>{{ $imprint['operator_name'] }}</p>
                    <p>{{ $imprint['street'] }}</p>
                    <p>{{ $imprint['postal_code'] }} {{ $imprint['city'] }}</p>
                    <p>{{ $imprint['country'] }}</p>
                </address>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 sm:p-10">
                <x-heading type="h2" class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('imprint.sections.contact') }}
                </x-heading>

                <x-paragraph class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('imprint.contact_hint') }}
                </x-paragraph>

                <div class="mt-4">
                    <x-primary-button type="button" data-imprint-reveal data-email-payload="{{ $imprint['email_payload'] }}"
                        data-phone-payload="{{ $imprint['phone_payload'] }}"
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
                    {{ __('imprint.sections.disclaimer') }}
                </x-heading>
                <x-paragraph class="mt-3 text-sm leading-7 text-slate-700 dark:text-slate-300">
                    {{ __('imprint.disclaimer') }}
                </x-paragraph>
            </section>
        </x-main>
    </main>
</x-marketing-layout>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const revealButton = document.querySelector('[data-imprint-reveal]');

            if (!revealButton) {
                return;
            }

            const decodePayload = (payload) => {
                try {
                    const raw = atob(payload);
                    const reversed = raw.split('').reverse().join('');

                    return reversed.replace(/[a-zA-Z]/g, (char) => {
                        const base = char <= 'Z' ? 65 : 97;
                        return String.fromCharCode(((char.charCodeAt(0) - base + 13) % 26) + base);
                    });
                } catch (error) {
                    return '';
                }
            };

            revealButton.addEventListener('click', () => {
                const email = decodePayload(revealButton.dataset.emailPayload ?? '');
                const phone = decodePayload(revealButton.dataset.phonePayload ?? '');
                const emailTarget = document.getElementById('imprint-email');
                const phoneTarget = document.getElementById('imprint-phone');

                if (emailTarget && email !== '') {
                    emailTarget.innerHTML =
                        `<a href="mailto:${email}" class="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300">${email}</a>`;
                }

                if (phoneTarget && phone !== '') {
                    const phoneHref = phone.replace(/[^0-9+]/g, '');
                    phoneTarget.innerHTML =
                        `<a href="tel:${phoneHref}" class="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300">${phone}</a>`;
                }

                revealButton.remove();
            });
        });
    </script>
@endpush
