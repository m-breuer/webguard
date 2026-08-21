<script lang="ts">
    import type { Snippet } from "svelte";
    interface Props { title: string; description: string; action?: Snippet; }
    let { title, description, action }: Props = $props();
</script>
<section class="grid justify-items-center gap-3 rounded-2xl border border-dashed border-wg-border px-6 py-12 text-center" aria-live="polite"><div class="grid size-11 place-items-center rounded-full bg-wg-surface-muted text-2xl text-wg-accent" aria-hidden="true">○</div><h2 class="text-lg font-bold">{title}</h2><p class="max-w-128 leading-6 text-wg-text-muted">{description}</p>{#if action}<div>{@render action()}</div>{/if}</section>
