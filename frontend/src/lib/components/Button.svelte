<script lang="ts">
    import type { Snippet } from "svelte";
    import type { HTMLButtonAttributes } from "svelte/elements";

    type Variant = "primary" | "secondary" | "danger" | "quiet";

    interface Props extends HTMLButtonAttributes {
        variant?: Variant;
        loading?: boolean;
        children?: Snippet;
    }

    let {
        variant = "primary",
        loading = false,
        disabled = false,
        children,
        class: className = "",
        ...attributes
    }: Props = $props();

    const variants: Record<Variant, string> = {
        primary: "bg-wg-accent text-wg-accent-contrast enabled:hover:bg-wg-accent-strong",
        secondary: "border-wg-border bg-wg-surface text-wg-text enabled:hover:border-wg-focus enabled:hover:bg-wg-surface-muted",
        danger: "bg-wg-danger text-white",
        quiet: "text-wg-text enabled:hover:border-wg-focus enabled:hover:bg-wg-surface-muted",
    };
</script>

<button
    {...attributes}
    class={`inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-transparent px-4 py-2.5 text-sm leading-5 font-semibold tracking-[0.035em] transition-[background-color,border-color,color,transform] duration-150 ease-out active:translate-y-px disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`}
    disabled={disabled || loading}
    aria-busy={loading}
>
    {#if loading}
        <span class="size-3.5 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span>
        <span class="sr-only">Saving</span>
    {/if}
    {@render children?.()}
</button>
