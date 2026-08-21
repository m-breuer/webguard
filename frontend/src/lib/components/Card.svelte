<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        title?: string;
        description?: string;
        actions?: Snippet;
        children?: Snippet;
    }

    let { title, description, actions, children }: Props = $props();
</script>

<section class="card">
    {#if title || description || actions}
        <header>
            <div>
                {#if title}<h2>{title}</h2>{/if}
                {#if description}<p>{description}</p>{/if}
            </div>
            {#if actions}<div class="actions">{@render actions()}</div>{/if}
        </header>
    {/if}
    <div class="content">{@render children?.()}</div>
</section>

<style>
    .card { overflow: hidden; border: 1px solid var(--wg-border); border-radius: 1rem; background: var(--wg-surface); box-shadow: var(--wg-shadow); }
    header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--wg-border); padding: 1.25rem 1.5rem; }
    h2, p { margin: 0; } h2 { font-size: 1.125rem; line-height: 1.5rem; } p { margin-top: 0.35rem; color: var(--wg-text-muted); font-size: 0.875rem; line-height: 1.35rem; }
    .actions { flex: none; } .content { padding: 1.5rem; }
    @media (max-width: 42rem) { header, .content { padding: 1.125rem; } header { flex-direction: column; } }
</style>
