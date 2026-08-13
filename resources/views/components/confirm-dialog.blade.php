<div
    x-data="confirmDialog()"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="cancel()"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="app-confirm-dialog-title"
    aria-describedby="app-confirm-dialog-message"
>
    <div
        x-show="open"
        class="fixed inset-0 bg-gray-950/70 backdrop-blur-sm"
        x-on:click="cancel()"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <div
        x-show="open"
        class="relative mx-auto mb-6 overflow-hidden rounded-lg border border-purple-100 bg-white shadow-2xl shadow-purple-950/20 transition-all sm:w-full sm:max-w-md dark:border-purple-900/50 dark:bg-gray-800 dark:shadow-black/40"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div class="border-b border-purple-100 bg-purple-50/70 px-6 py-5 dark:border-purple-900/50 dark:bg-purple-950/30">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 ring-8 ring-red-50 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-950/30">
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.516-2.625L8.485 2.495ZM10 5.5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5.5Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                <div>
                    <h2
                        id="app-confirm-dialog-title"
                        class="text-lg font-semibold text-gray-950 dark:text-gray-50"
                        x-text="title"
                    ></h2>
                    <p
                        id="app-confirm-dialog-message"
                        class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300"
                        x-text="message"
                    ></p>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 px-6 py-5 sm:flex-row sm:justify-end">
            <x-secondary-button type="button" x-on:click="cancel()" class="justify-center" data-confirm-cancel-button>
                <span x-text="cancelText"></span>
            </x-secondary-button>
            <x-danger-button type="button" x-on:click="confirm()" class="justify-center">
                <span x-text="confirmText"></span>
            </x-danger-button>
        </div>
    </div>
</div>
