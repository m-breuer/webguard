<script lang="ts">
    import type { HTMLInputAttributes } from "svelte/elements";

    interface Props extends Omit<HTMLInputAttributes, "checked" | "type"> {
        checked?: boolean;
        group?: string[];
    }

    let { checked = $bindable(false), group = $bindable<string[] | undefined>(), class: className = "", ...attributes }: Props = $props();

    function groupValue(): string {
        return String(attributes.value ?? "");
    }

    function toggleGroup(event: Event): void {
        const input = event.currentTarget as HTMLInputElement;
        const value = input.value;
        const current = group ?? [];

        group = input.checked
            ? current.includes(value) ? current : [...current, value]
            : current.filter((item) => item !== value);
    }
</script>

{#if group}
    <input
        {...attributes}
        checked={group.includes(groupValue())}
        type="checkbox"
        onchange={toggleGroup}
        class={`size-5 shrink-0 rounded-full border-wg-border text-wg-accent accent-wg-accent focus:ring-2 focus:ring-wg-focus/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wg-focus disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
    />
{:else}
    <input
        {...attributes}
        bind:checked
        type="checkbox"
        class={`size-5 shrink-0 rounded-full border-wg-border text-wg-accent accent-wg-accent focus:ring-2 focus:ring-wg-focus/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wg-focus disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
    />
{/if}
