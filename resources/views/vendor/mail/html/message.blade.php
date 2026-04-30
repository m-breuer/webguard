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

<a href="{{ route('monitoring-locations') }}">{{ __('monitoring_locations.footer_link') }}</a>
<a href="{{ route('imprint') }}">{{ __('imprint.footer_link') }}</a>
<a href="{{ route('terms-of-use') }}">{{ __('legal.terms_of_use.footer_link') }}</a>
<a href="{{ route('gdpr') }}">{{ __('gdpr.footer_link') }}</a>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
