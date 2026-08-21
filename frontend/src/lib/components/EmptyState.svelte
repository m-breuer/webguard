<script lang="ts">
    import type { Snippet } from "svelte";
    interface Props { title: string; description: string; action?: Snippet; }
    let { title, description, action }: Props = $props();
</script>
<section class="empty" aria-live="polite"><div class="icon" aria-hidden="true">○</div><h2>{title}</h2><p>{description}</p>{#if action}<div>{@render action()}</div>{/if}</section>
<style>.empty { display: grid; justify-items: center; gap: 0.75rem; border: 1px dashed var(--wg-border); border-radius: 1rem; padding: 3rem 1.5rem; text-align: center; } .icon { display: grid; width: 2.75rem; height: 2.75rem; place-items: center; border-radius: 50%; background: var(--wg-surface-muted); color: var(--wg-accent); font-size: 2rem; } h2, p { margin: 0; } h2 { font-size: 1.125rem; } p { max-width: 32rem; color: var(--wg-text-muted); line-height: 1.5; }</style>
