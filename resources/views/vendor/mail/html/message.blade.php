<x-mail::layout>
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
&copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('legal.footer.content') }}

@if (config('app.marketing_url'))
<a href="{{ config('app.marketing_url') }}">{{ __('app.marketing_site') }}</a>
@endif
<a href="{{ route('imprint') }}">{{ __('imprint.footer_link') }}</a>
<a href="{{ route('terms-of-use') }}">{{ __('legal.terms_of_use.footer_link') }}</a>
<a href="{{ route('gdpr') }}">{{ __('gdpr.footer_link') }}</a>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
