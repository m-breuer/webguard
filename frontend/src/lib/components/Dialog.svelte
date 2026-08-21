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

<dialog bind:this={dialog} aria-label={title} oncancel={handleCancel} onclose={handleDialogClose} onclick={handleBackdropClick}>
    <section class="panel">
        <header>
            <div>
                <h2>{title}</h2>
                {#if description}<p>{description}</p>{/if}
            </div>
            <button type="button" class="close" aria-label={closeLabel} onclick={close}>×</button>
        </header>
        <div class="content">{@render children?.()}</div>
    </section>
</dialog>

<style>
    dialog {
        width: min(42rem, calc(100% - 2rem));
        max-height: min(46rem, calc(100% - 2rem));
        padding: 0;
        border: 1px solid var(--wg-border);
        border-radius: 1rem;
        background: var(--wg-surface);
        color: var(--wg-text);
        box-shadow: var(--wg-shadow);
    }

    dialog::backdrop {
        background: rgb(15 23 42 / 65%);
    }

    .panel {
        display: grid;
        max-height: inherit;
        grid-template-rows: auto minmax(0, 1fr);
    }

    header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 1px solid var(--wg-border);
        padding: 1.25rem 1.5rem;
    }

    h2,
    p {
        margin: 0;
    }

    h2 {
        font-size: 1.25rem;
        line-height: 1.75rem;
    }

    p {
        margin-top: 0.35rem;
        color: var(--wg-text-muted);
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    .close {
        display: inline-grid;
        width: 2.25rem;
        height: 2.25rem;
        flex: none;
        place-items: center;
        border: 1px solid var(--wg-border);
        border-radius: 0.625rem;
        background: transparent;
        color: var(--wg-text);
        font-size: 1.5rem;
        line-height: 1;
    }

    .close:hover {
        background: var(--wg-surface-muted);
    }

    .content {
        overflow: auto;
        padding: 1.5rem;
    }
</style>
