@props([
    'id' => 'language-switch',
    'class' => '',
    'variant' => 'default',
])

@php
    use App\Enums\SupportedLanguage;

    $languages = collect(SupportedLanguage::cases())
        ->mapWithKeys(fn (SupportedLanguage $language): array => [
            $language->value => [
                'label' => $language->label(),
                'code' => strtoupper($language->value),
            ],
        ])
        ->all();

    $currentLocale = app()->getLocale();
    if (! array_key_exists($currentLocale, $languages)) {
        $currentLocale = SupportedLanguage::default()->value;
    }

    $isMarketingVariant = $variant === 'marketing';

    $triggerClasses = $isMarketingVariant
        ? 'focus:outline-hidden inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-700 transition hover:border-slate-400 hover:bg-slate-100 focus:ring-2 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800 dark:focus:ring-emerald-300'
        : 'focus:outline-hidden inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 transition hover:border-gray-400 hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:border-gray-500 dark:hover:bg-gray-600';

    $menuClasses = $isMarketingVariant
        ? 'py-1 bg-white ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700'
        : 'py-1 bg-white dark:bg-gray-800';
@endphp

<div class="{{ $class }}">
    <x-dropdown align="right" width="w-40" :content-classes="$menuClasses">
        <x-slot name="trigger">
            <button id="{{ $id }}" type="button" aria-label="{{ __('profile.fields.language') }}"
                title="{{ $languages[$currentLocale]['label'] }}" class="{{ $triggerClasses }}">
                <x-language-flag :locale="$currentLocale" class="h-5 w-5 rounded-full" />
            </button>
        </x-slot>

        <x-slot name="content">
            @foreach ($languages as $locale => $language)
                <a href="{{ route('locale.switch', ['locale' => $locale]) }}"
                    class="{{ $isMarketingVariant
                        ? 'flex w-full items-center justify-between gap-3 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800'
                        : 'flex w-full items-center justify-between gap-3 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    <span class="flex items-center gap-2">
                        <x-language-flag :locale="$locale" class="h-5 w-5 rounded-full" />
                        <span class="font-semibold">{{ $language['code'] }}</span>
                    </span>

                    @if ($locale === $currentLocale)
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M16.704 5.29a1 1 0 01.006 1.414l-8 8a1 1 0 01-1.42-.004l-4-4a1 1 0 111.414-1.414l3.293 3.292 7.296-7.29a1 1 0 011.41.002z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endforeach
        </x-slot>
    </x-dropdown>
</div>
