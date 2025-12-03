@props(['href' => null])

@if ($href)
    <a href="{{ $href }}"
        {{ $attributes->merge(['class' => 'cursor-pointer inline-flex w-max items-center px-4 py-2 bg-white border border-purple-500 rounded-md font-semibold  text-purple-600 uppercase tracking-widest hover:bg-purple-50 focus:bg-purple-50 active:bg-purple-100 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-gray-700 dark:text-purple-300']) }}>
        {{ $slot }}
    </a>
@else
    <button
        {{ $attributes->merge(['type' => 'submit', 'class' => 'cursor-pointer inline-flex w-max items-center px-4 py-2 bg-white border border-purple-500 rounded-md font-semibold  text-purple-600 uppercase tracking-widest hover:bg-purple-50 focus:bg-purple-50 active:bg-purple-100 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-gray-700 dark:text-purple-300']) }}>
        {{ $slot }}
    </button>
@endif
