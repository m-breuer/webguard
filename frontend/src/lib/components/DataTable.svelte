<script lang="ts">
    import type { Snippet } from "svelte";
    interface Props { caption: string; children?: Snippet; }
    let { caption, children }: Props = $props();
</script>
<div class="table-wrap"><table><caption>{caption}</caption>{@render children?.()}</table></div>
<style>.table-wrap { overflow-x: auto; border: 1px solid var(--wg-border); border-radius: 0.875rem; } table { width: 100%; border-collapse: collapse; text-align: left; } caption { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; } :global(th) { background: var(--wg-surface-muted); color: var(--wg-text-muted); font-size: 0.75rem; letter-spacing: 0.06em; text-transform: uppercase; } :global(th), :global(td) { border-bottom: 1px solid var(--wg-border); padding: 0.875rem 1rem; vertical-align: middle; } :global(tbody tr:last-child td) { border-bottom: 0; }</style>
