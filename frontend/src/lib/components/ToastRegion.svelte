<script lang="ts">
    export type ToastTone = "success" | "error" | "info";
    export interface ToastMessage { id: string; tone: ToastTone; message: string; }
    interface Props { items?: ToastMessage[]; onDismiss?: (id: string) => void; }
    let { items = [], onDismiss }: Props = $props();
</script>
<section class="toasts" aria-label="Notifications" aria-live="polite">
    {#each items as item (item.id)}
        <div class={`toast ${item.tone}`} role={item.tone === "error" ? "alert" : "status"}>
            <p>{item.message}</p><button type="button" aria-label="Dismiss notification" onclick={() => onDismiss?.(item.id)}>×</button>
        </div>
    {/each}
</section>
<style>.toasts { position: fixed; right: 1rem; bottom: 1rem; z-index: 40; display: grid; width: min(24rem, calc(100vw - 2rem)); gap: 0.75rem; } .toast { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border: 1px solid var(--wg-border); border-left-width: 4px; border-radius: 0.75rem; background: var(--wg-surface); box-shadow: var(--wg-shadow); padding: 0.875rem 1rem; } .success { border-left-color: #16a34a; } .error { border-left-color: var(--wg-danger); } .info { border-left-color: var(--wg-accent); } p { margin: 0; font-size: 0.875rem; font-weight: 650; } button { border: 0; background: transparent; color: var(--wg-text-muted); font-size: 1.25rem; }</style>
