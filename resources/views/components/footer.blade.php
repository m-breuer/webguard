<footer class="border-t border-slate-200/80 bg-white/80 dark:border-slate-800/70 dark:bg-slate-950/80">
    <x-main class="w-full py-5">
        <div class="flex flex-col gap-3 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
            <x-paragraph class="max-w-2xl text-sm leading-6 text-gray-500">
                &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('legal.footer.content') }}
            </x-paragraph>

            <nav class="w-full sm:w-auto" aria-label="{{ __('imprint.footer_nav_aria') }}">
                <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:justify-end">
                    <li>
                        <a href="{{ route('monitoring-locations') }}"
                            class="text-sm font-medium text-slate-600 transition hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300">
                            {{ __('monitoring_locations.footer_link') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('imprint') }}"
                            class="text-sm font-medium text-slate-600 transition hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300">
                            {{ __('imprint.footer_link') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('terms-of-use') }}"
                            class="text-sm font-medium text-slate-600 transition hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300">
                            {{ __('legal.terms_of_use.footer_link') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('gdpr') }}"
                            class="text-sm font-medium text-slate-600 transition hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300">
                            {{ __('gdpr.footer_link') }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </x-main>
</footer>
