<script lang="ts">
    import { tick } from "svelte";
    import type { Snippet } from "svelte";

    interface Props {
        open?: boolean;
        title: string;
        description?: string;
        closeLabel?: string;
        children?: Snippet;
        onclose?: () => void;
    }

    let {
        open = $bindable(false),
        title,
        description,
        closeLabel = "Close dialog",
        children,
        onclose,
    }: Props = $props();
    let dialog: HTMLDialogElement;

    $effect(() => {
        if (open) {
            void tick().then(() => {
                if (!dialog.open) {
                    dialog.showModal();
                }
            });
        } else if (dialog?.open) {
            dialog.close();
        }
    });

    function close(): void {
        open = false;
        onclose?.();
    }

    function handleDialogClose(): void {
        if (open) {
            close();
        }
    }

    function handleCancel(event: Event): void {
        event.preventDefault();
        close();
    }

    function handleBackdropClick(event: MouseEvent): void {
        if (event.target === dialog) {
            close();
        }
    }
</script>

<dialog
    bind:this={dialog}
    aria-label={title}
    class="m-auto max-h-[min(46rem,calc(100%_-_2rem))] w-[min(42rem,calc(100%_-_2rem))] rounded-2xl border border-wg-border bg-wg-surface p-0 text-wg-text shadow-wg-surface backdrop:bg-slate-900/65"
    oncancel={handleCancel}
    onclose={handleDialogClose}
    onclick={handleBackdropClick}
>
    <section class="grid [max-height:inherit] grid-rows-[auto_minmax(0,1fr)]">
        <header class="flex items-start justify-between gap-4 border-b border-wg-border px-6 py-5">
            <div>
                <h2 class="text-xl leading-7 font-bold">{title}</h2>
                {#if description}<p class="mt-[0.35rem] text-sm leading-5 text-wg-text-muted">{description}</p>{/if}
            </div>
            <button
                type="button"
                class="inline-grid size-9 shrink-0 place-items-center rounded-[0.625rem] border border-wg-border bg-transparent text-2xl leading-none text-wg-text enabled:hover:bg-wg-surface-muted"
                aria-label={closeLabel}
                onclick={close}
            >×</button>
        </header>
        <div class="overflow-auto p-6">{@render children?.()}</div>
    </section>
</dialog>
