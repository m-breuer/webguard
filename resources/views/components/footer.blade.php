@php
    $legalLinksExternal = \App\Support\LegalLinks::isExternal();
@endphp

<footer class="border-t border-slate-200/80 bg-white/80 dark:border-slate-800/70 dark:bg-slate-950/80">
    <x-main class="w-full py-5">
        <div class="flex flex-col gap-3 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
            <x-paragraph class="max-w-2xl text-sm leading-6 text-gray-500">
                &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('app.legal.footer_content') }}
            </x-paragraph>

            <nav class="w-full sm:w-auto" aria-label="{{ __('app.legal.footer_nav_aria') }}">
                <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:justify-end">
                    @if (config('app.marketing_url'))
                        <li>
                            <a href="{{ config('app.marketing_url') }}" target="_blank" rel="noopener"
                                class="text-sm font-medium text-slate-600 transition hover:text-purple-700 dark:text-slate-300 dark:hover:text-purple-300">
                                {{ __('app.marketing_site') }}
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('scribe') }}"
                            class="text-sm font-medium text-slate-600 transition hover:text-purple-700 dark:text-slate-300 dark:hover:text-purple-300">
                            {{ __('app.api_documentation') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ \App\Support\LegalLinks::imprint() }}"
                            @if ($legalLinksExternal) target="_blank" rel="noopener" @endif
                            class="text-sm font-medium text-slate-600 transition hover:text-purple-700 dark:text-slate-300 dark:hover:text-purple-300">
                            {{ __('app.legal.imprint') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ \App\Support\LegalLinks::termsOfUse() }}"
                            @if ($legalLinksExternal) target="_blank" rel="noopener" @endif
                            class="text-sm font-medium text-slate-600 transition hover:text-purple-700 dark:text-slate-300 dark:hover:text-purple-300">
                            {{ __('app.legal.terms_of_use') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ \App\Support\LegalLinks::privacyPolicy() }}"
                            @if ($legalLinksExternal) target="_blank" rel="noopener" @endif
                            class="text-sm font-medium text-slate-600 transition hover:text-purple-700 dark:text-slate-300 dark:hover:text-purple-300">
                            {{ __('app.legal.privacy_policy') }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </x-main>
</footer>
