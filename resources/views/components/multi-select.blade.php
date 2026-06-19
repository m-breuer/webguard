@props([
    'id',
    'name',
    'options' => [],
    'selected' => [],
    'placeholder' => __('app.multi_select.placeholder'),
    'searchPlaceholder' => __('app.multi_select.search_placeholder'),
    'selectAllLabel' => __('app.multi_select.select_all'),
    'allSelectedLabel' => __('app.multi_select.all_selected'),
    'noOptionsLabel' => __('app.multi_select.no_options'),
    'noResultsLabel' => __('app.multi_select.no_results'),
    'removeLabel' => __('app.multi_select.remove'),
    'clearLabel' => __('app.multi_select.clear'),
])

@php
    $fieldId = $id;
    $inputName = str_ends_with($name, '[]') ? $name : $name . '[]';
    $normalizedOptions = collect($options)->map(static fn ($option): array => [
        'value' => (string) data_get($option, 'value', data_get($option, 'id')),
        'label' => (string) data_get($option, 'label', data_get($option, 'name')),
    ])->filter(static fn (array $option): bool => $option['value'] !== '' && $option['label'] !== '')->values();
    $normalizedSelected = array_values(array_filter(
        array_map(static fn (mixed $value): string => (string) $value, is_array($selected) ? $selected : []),
        static fn (string $value): bool => $value !== ''
    ));
@endphp

<div {{ $attributes->class('relative') }}
    x-data="{
        open: false,
        query: '',
        selected: @js($normalizedSelected),
        options: @js($normalizedOptions),
        placeholder: @js($placeholder),
        removeLabel: @js($removeLabel),
        get selectedOptions() {
            return this.options.filter(option => this.selected.includes(option.value));
        },
        get filteredOptions() {
            const query = this.query.trim().toLowerCase();

            if (query === '') {
                return this.options;
            }

            return this.options.filter(option => option.label.toLowerCase().includes(query));
        },
        get allFilteredSelected() {
            return this.filteredOptions.length > 0
                && this.filteredOptions.every(option => this.selected.includes(option.value));
        },
        get hasPartialFilteredSelection() {
            return !this.allFilteredSelected
                && this.filteredOptions.some(option => this.selected.includes(option.value));
        },
        isSelected(value) {
            return this.selected.includes(value);
        },
        toggle(value) {
            if (this.isSelected(value)) {
                this.selected = this.selected.filter(selectedValue => selectedValue !== value);

                return;
            }

            this.selected.push(value);
        },
        remove(value) {
            this.selected = this.selected.filter(selectedValue => selectedValue !== value);
        },
        clear() {
            this.selected = [];
            this.query = '';
        },
        toggleFilteredSelection() {
            if (this.filteredOptions.length === 0) {
                return;
            }

            const filteredValues = this.filteredOptions.map(option => option.value);

            if (this.allFilteredSelected) {
                this.selected = this.selected.filter(value => !filteredValues.includes(value));

                return;
            }

            this.selected = Array.from(new Set([...this.selected, ...filteredValues]));
        },
    }"
    x-on:keydown.escape.window="open = false">
    <template x-for="value in selected" :key="value">
        <input type="hidden" name="{{ $inputName }}" :value="value">
    </template>

    <div class="flex min-h-11 w-full items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition focus-within:border-purple-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-purple-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        x-on:click="open = true; $nextTick(() => $refs.search?.focus())"
        x-bind:class="{ 'border-purple-500 ring-2 ring-purple-500/30': open }">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <template x-for="option in selectedOptions" :key="option.value">
                <span class="inline-flex max-w-full items-center gap-1 rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <span class="truncate" x-text="option.label"></span>
                    <button type="button"
                        class="rounded-sm px-1 text-gray-500 hover:bg-gray-200 hover:text-gray-700 focus:bg-gray-200 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100 dark:focus:bg-gray-700"
                        x-on:click.stop="remove(option.value)"
                        x-bind:aria-label="removeLabel + ' ' + option.label">&times;</button>
                </span>
            </template>

            <input id="{{ $fieldId }}" type="text"
                class="min-w-28 flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-500 focus:ring-0 dark:text-gray-100 dark:placeholder:text-gray-400"
                x-ref="search"
                x-model="query"
                x-on:focus="open = true"
                x-on:keydown.backspace="if (query === '' && selected.length > 0) remove(selected[selected.length - 1])"
                x-bind:placeholder="selected.length === 0 ? placeholder : ''"
                aria-haspopup="listbox"
                x-bind:aria-expanded="open.toString()">
        </div>

        <div class="flex shrink-0 items-center gap-2 text-gray-500 dark:text-gray-400">
            <button type="button" x-cloak x-show="selected.length > 0"
                class="rounded-sm px-1 text-lg leading-none hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500/30 dark:hover:text-gray-100"
                x-on:click.stop="clear()"
                aria-label="{{ $clearLabel }}">&times;</button>
            <button type="button"
                class="rounded-sm p-0.5 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500/30 dark:hover:text-gray-100"
                x-on:click.stop="open = !open; $nextTick(() => $refs.search?.focus())"
                x-bind:aria-expanded="open.toString()"
                aria-controls="{{ $fieldId }}_options"
                aria-label="{{ $searchPlaceholder }}">
                <svg class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    <div id="{{ $fieldId }}_options" x-cloak x-show="open" x-transition x-on:click.outside="open = false"
        class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 text-sm shadow-lg dark:border-gray-700 dark:bg-gray-900"
        role="listbox"
        aria-multiselectable="true">
        <button type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-left font-medium text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-200 dark:hover:bg-gray-800 dark:focus:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
            x-on:click="toggleFilteredSelection()"
            x-bind:disabled="filteredOptions.length === 0">
            <input type="checkbox"
                class="pointer-events-none rounded-sm border-gray-300 text-purple-600 shadow-xs focus:ring-0 dark:border-gray-600 dark:bg-gray-800"
                tabindex="-1"
                x-bind:checked="allFilteredSelected"
                x-bind:indeterminate="hasPartialFilteredSelection">
            <span>{{ $selectAllLabel }}</span>
        </button>

        <template x-if="options.length === 0">
            <div class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $noOptionsLabel }}</div>
        </template>

        <template x-if="options.length > 0 && filteredOptions.length === 0">
            <div class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $noResultsLabel }}</div>
        </template>

        @foreach ($normalizedOptions as $option)
            <button type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-200 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                x-show="filteredOptions.some(option => option.value === @js($option['value']))"
                x-on:click="toggle(@js($option['value']))"
                x-bind:class="{ 'bg-gray-100 dark:bg-gray-800': isSelected(@js($option['value'])) }"
                role="option"
                x-bind:aria-selected="isSelected(@js($option['value'])).toString()">
                <input type="checkbox"
                    class="pointer-events-none rounded-sm border-gray-300 text-purple-600 shadow-xs focus:ring-0 dark:border-gray-600 dark:bg-gray-800"
                    tabindex="-1"
                    x-bind:checked="isSelected(@js($option['value']))">
                <span class="truncate">{{ $option['label'] }}</span>
            </button>
        @endforeach

        <template x-if="filteredOptions.length > 0 && allFilteredSelected">
            <div class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $allSelectedLabel }}</div>
        </template>
    </div>
</div>
