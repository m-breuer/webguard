<x-mail::layout>
@php
    $legalLinksExternal = \App\Support\LegalLinks::isExternal();
@endphp
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')" />
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
&copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('app.legal.footer_content') }}

@if (config('app.marketing_url'))
<a href="{{ config('app.marketing_url') }}" target="_blank" rel="noopener">{{ __('app.marketing_site') }}</a>
@endif
<a href="{{ \App\Support\LegalLinks::imprint() }}" @if ($legalLinksExternal) target="_blank" rel="noopener" @endif>{{ __('app.legal.imprint') }}</a>
<a href="{{ \App\Support\LegalLinks::termsOfUse() }}" @if ($legalLinksExternal) target="_blank" rel="noopener" @endif>{{ __('app.legal.terms_of_use') }}</a>
<a href="{{ \App\Support\LegalLinks::privacyPolicy() }}" @if ($legalLinksExternal) target="_blank" rel="noopener" @endif>{{ __('app.legal.privacy_policy') }}</a>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
