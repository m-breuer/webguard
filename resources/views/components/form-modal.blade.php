@props([
    'name',
    'title',
    'description' => null,
    'show' => false,
    'maxWidth' => '2xl',
])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        '6xl' => 'sm:max-w-6xl',
        '7xl' => 'sm:max-w-7xl',
    ][$maxWidth] ?? 'sm:max-w-2xl';
    $modalId = \Illuminate\Support\Str::slug($name);
@endphp

<div
    x-data="{
        open: @js($show),
        submitting: false,
        previousFocus: null,
        focusables() {
            return [...$el.querySelectorAll('a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])')]
                .filter((element) => ! element.hasAttribute('disabled'));
        },
        focusFirst() {
            this.$nextTick(() => this.focusables()[0]?.focus());
        },
        focusNext(event) {
            const elements = this.focusables();

            if (! elements.length) {
                return;
            }

            const currentIndex = elements.indexOf(document.activeElement);
            const nextIndex = event.shiftKey
                ? (currentIndex <= 0 ? elements.length - 1 : currentIndex - 1)
                : (currentIndex + 1) % elements.length;

            elements[nextIndex]?.focus();
        },
        lockBody(value) {
            document.body.classList.toggle('overflow-y-hidden', value);
        },
        openModal() {
            this.previousFocus = document.activeElement;
            this.submitting = false;
            this.open = true;
            this.lockBody(true);
            this.focusFirst();
        },
        closeModal() {
            this.open = false;
            this.submitting = false;
            this.lockBody(false);
            this.$nextTick(() => this.previousFocus?.focus());
        },
    }"
    x-init="
        if (open) {
            lockBody(true);
            focusFirst();
        }
        $watch('open', (value) => lockBody(value));
    "
    x-on:open-form-modal.window="$event.detail === @js($name) ? openModal() : null"
    x-on:close-form-modal.window="$event.detail === @js($name) ? closeModal() : null"
    x-on:keydown.escape.window="if (open) closeModal();"
    x-on:keydown.tab.prevent="if (open) focusNext($event);"
    x-show="open"
    x-cloak
    data-form-modal="{{ $name }}"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    @if ($description) aria-describedby="{{ $modalId }}-description" @endif
    x-bind:aria-busy="submitting"
>
    <div
        x-show="open"
        x-on:click="closeModal()"
        class="fixed inset-0 bg-gray-950/70 backdrop-blur-sm"
        aria-hidden="true"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <div
        x-show="open"
        x-on:click.stop
        x-on:submit.capture="
            submitting = true;
            $event.target.querySelectorAll('button[type=submit]').forEach((button) => (button.disabled = true));
        "
        class="relative mx-auto mb-6 flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800 {{ $maxWidth }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-gray-700">
            <div>
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-950 dark:text-gray-50">
                    {{ $title }}
                </h2>

                @if ($description)
                    <p id="{{ $modalId }}-description" class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        {{ $description }}
                    </p>
                @endif
            </div>

            <button
                type="button"
                x-on:click="closeModal()"
                class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:ring-2 focus:ring-purple-500 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                aria-label="{{ __('button.cancel') }}"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" />
                </svg>
            </button>
        </div>

        <div class="min-h-0 overflow-y-auto">{{ $slot }}</div>
    </div>
</div>
