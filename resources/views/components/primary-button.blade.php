@props(['href' => null])

@if ($href)
    <a href="{{ $href }}"
        {{ $attributes->merge(['class' => 'cursor-pointer inline-flex w-max items-center px-4 py-2 bg-purple-500 border border-transparent rounded-md font-semibold  text-white uppercase tracking-widest hover:bg-purple-600 focus:bg-purple-600 active:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-purple-600 dark:hover:bg-purple-500']) }}>
        {{ $slot }}
    </a>
@else
    <button
        {{ $attributes->merge(['type' => 'submit', 'class' => 'cursor-pointer inline-flex w-max items-center px-4 py-2 bg-purple-500 border border-transparent rounded-md font-semibold  text-white uppercase tracking-widest hover:bg-purple-600 focus:bg-purple-600 active:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-purple-600 dark:hover:bg-purple-500']) }}>
        {{ $slot }}
    </button>
@endif
