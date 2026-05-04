<div x-data="confirmDialog()" x-show="open" x-cloak x-on:keydown.escape.window="cancel()"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" role="dialog" aria-modal="true"
    aria-labelledby="app-confirm-dialog-title">
    <div x-show="open" class="fixed inset-0 bg-gray-500 opacity-75" x-on:click="cancel()"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-75" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-75" x-transition:leave-end="opacity-0"></div>

    <div x-show="open"
        class="relative mx-auto mb-6 overflow-hidden rounded-lg bg-white p-6 shadow-xl transition-all dark:bg-gray-800 sm:w-full sm:max-w-md"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        <h2 id="app-confirm-dialog-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100"
            x-text="title"></h2>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300" x-text="message"></p>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-secondary-button type="button" x-on:click="cancel()" class="justify-center"
                data-confirm-cancel-button>
                <span x-text="cancelText"></span>
            </x-secondary-button>
            <x-danger-button type="button" x-on:click="confirm()" class="justify-center">
                <span x-text="confirmText"></span>
            </x-danger-button>
        </div>
    </div>
</div>
