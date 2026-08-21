<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        label: string;
        hint?: string;
        error?: string;
        required?: boolean;
        children?: Snippet;
    }

    let { label, hint, error, required = false, children }: Props = $props();
</script>

<label class="field">
    <span class="label">
        {label}
        {#if required}<span aria-hidden="true">*</span>{/if}
    </span>
    {@render children?.()}
    {#if error}
        <span class="error" role="alert">{error}</span>
    {:else if hint}
        <span class="hint">{hint}</span>
    {/if}
</label>

<style>
    .field {
        display: grid;
        gap: 0.45rem;
        color: var(--wg-text);
    }

    .label {
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1.25rem;
    }

    .hint,
    .error {
        font-size: 0.8125rem;
        line-height: 1.25rem;
    }

    .hint {
        color: var(--wg-text-muted);
    }

    .error {
        color: var(--wg-danger);
    }
</style>
