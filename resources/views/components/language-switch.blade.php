@props([
    'id' => 'language-switch',
    'class' => '',
])

@php
    use App\Enums\SupportedLanguage;

    $languages = SupportedLanguage::toArray();
    $currentLocale = app()->getLocale();
@endphp

<form method="POST" action="{{ route('locale.switch') }}" class="{{ $class }}">
    @csrf
    <label for="{{ $id }}" class="sr-only">{{ __('profile.fields.language') }}</label>
    <select id="{{ $id }}" name="locale"
        aria-label="{{ __('profile.fields.language') }}"
        onchange="this.form.submit()"
        class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        @foreach ($languages as $code => $label)
            <option value="{{ $code }}" @selected($code === $currentLocale)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <noscript>
        <button type="submit"
            class="ml-2 rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700 dark:border-gray-600 dark:text-gray-200">
            {{ __('button.update') }}
        </button>
    </noscript>
</form>
