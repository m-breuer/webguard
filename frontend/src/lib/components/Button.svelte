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
        primary: "primary",
        secondary: "secondary",
        danger: "danger",
        quiet: "quiet",
    };
</script>

<button
    {...attributes}
    class={`button ${variants[variant]} ${className}`}
    disabled={disabled || loading}
    aria-busy={loading}
>
    {#if loading}
        <span class="spinner" aria-hidden="true"></span>
        <span class="sr-only">Saving</span>
    {/if}
    {@render children?.()}
</button>

<style>
    .button {
        display: inline-flex;
        min-height: 2.75rem;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border: 1px solid transparent;
        border-radius: 0.75rem;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: 0.035em;
        line-height: 1.25rem;
        transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, transform 150ms ease;
    }

    .button:active:not(:disabled) {
        transform: translateY(1px);
    }

    .button:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }

    .primary {
        background: var(--wg-accent);
        color: var(--wg-accent-contrast);
    }

    .primary:hover:not(:disabled) {
        background: var(--wg-accent-strong);
    }

    .secondary {
        border-color: var(--wg-border);
        background: var(--wg-surface);
        color: var(--wg-text);
    }

    .secondary:hover:not(:disabled),
    .quiet:hover:not(:disabled) {
        border-color: var(--wg-focus);
        background: var(--wg-surface-muted);
    }

    .danger {
        background: var(--wg-danger);
        color: white;
    }

    .quiet {
        color: var(--wg-text);
    }

    .spinner {
        width: 0.875rem;
        height: 0.875rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 999px;
        animation: spin 700ms linear infinite;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
