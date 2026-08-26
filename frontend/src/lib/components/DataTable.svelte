<script lang="ts">
    import type { Snippet } from "svelte";
    interface Props { caption: string; children?: Snippet; }
    let { caption, children }: Props = $props();
</script>
<div class="overflow-x-auto rounded-md border border-wg-border bg-wg-surface shadow-xs [&_caption]:sr-only [&_table]:w-full [&_table]:border-collapse [&_table]:text-left [&_tbody_tr:last-child_td]:border-b-0 [&_td]:border-b [&_td]:border-wg-border [&_td]:px-4 [&_td]:py-3.5 [&_td]:align-middle [&_th]:border-b [&_th]:border-wg-border [&_th]:bg-wg-surface-muted [&_th]:px-4 [&_th]:py-3.5 [&_th]:align-middle [&_th]:text-xs [&_th]:tracking-[0.06em] [&_th]:text-wg-text-muted [&_th]:uppercase"><table><caption>{caption}</caption>{@render children?.()}</table></div>
