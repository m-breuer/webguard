<script lang="ts">
    import type { HTMLInputAttributes } from "svelte/elements";

    interface Props extends Omit<HTMLInputAttributes, "checked" | "type"> {
        checked?: boolean;
        group?: string[];
    }

    let { checked = $bindable(false), group = $bindable<string[] | undefined>(), class: className = "", ...attributes }: Props = $props();
</script>

{#if group}
    <input
        {...attributes}
        bind:group
        type="checkbox"
        class={`size-5 shrink-0 rounded border-wg-border text-wg-accent accent-wg-accent focus:ring-2 focus:ring-wg-focus/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wg-focus disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
    />
{:else}
    <input
        {...attributes}
        bind:checked
        type="checkbox"
        class={`size-5 shrink-0 rounded border-wg-border text-wg-accent accent-wg-accent focus:ring-2 focus:ring-wg-focus/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wg-focus disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
    />
{/if}
