@props(['sort' => null])

<th scope="col" class="px-6 py-3 text-left font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
    @if ($sort)
        <button
            type="button"
            class="inline-flex items-center gap-2 text-left tracking-wider uppercase transition hover:text-purple-600 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:outline-hidden dark:hover:text-purple-300"
            @click="sortBy('{{ $sort }}')"
            :aria-sort="isSorted('{{ $sort }}') ? direction : 'none'"
        >
            <span>{{ $slot }}</span>
            <span class="text-xs" x-text="sortIndicator('{{ $sort }}')"></span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
