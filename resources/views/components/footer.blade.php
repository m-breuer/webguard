<footer class="border-t border-slate-200/80 bg-white/80 dark:border-slate-800/70 dark:bg-slate-950/80">
    <x-main class="w-full py-5">
        <div class="flex flex-col items-center justify-between gap-2 text-center sm:flex-row sm:text-left">
            <x-paragraph class="text-sm text-gray-500">
                &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('legal.footer.content') }}
            </x-paragraph>

            <nav aria-label="{{ __('imprint.footer_nav_aria') }}">
                <ul class="flex items-center gap-4">
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
