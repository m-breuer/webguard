<script lang="ts">
    import type { Snippet } from "svelte";
    import type { HTMLSelectAttributes } from "svelte/elements";

    interface Props extends Omit<HTMLSelectAttributes, "value"> {
        value?: string | string[];
        children?: Snippet;
    }

    let { value = $bindable(), children, class: className = "", ...attributes }: Props = $props();
</script>

<select
    {...attributes}
    bind:value
    class={`min-h-11 w-full rounded-md border border-wg-border bg-wg-surface px-3 py-2 text-sm text-wg-text shadow-xs transition-colors focus:border-wg-accent focus:ring-2 focus:ring-wg-focus/40 disabled:cursor-not-allowed disabled:bg-wg-surface-muted disabled:text-wg-text-muted ${className}`}
>
    {@render children?.()}
</select>
