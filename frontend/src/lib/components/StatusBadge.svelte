<script lang="ts">
    export type StatusTone = "healthy" | "degraded" | "danger" | "neutral" | "paused";

    interface Props { tone?: StatusTone; label: string; }
    let { tone = "neutral", label }: Props = $props();
</script>

<span class={`badge ${tone}`}><span class="dot" aria-hidden="true"></span>{label}</span>

<style>
    .badge { display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 999px; padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 800; line-height: 1; }
    .dot { width: 0.45rem; height: 0.45rem; border-radius: 50%; background: currentColor; }
    .healthy { background: #dcfce7; color: #166534; } .degraded { background: #fef3c7; color: #92400e; } .danger { background: #fee2e2; color: #b91c1c; } .neutral { background: var(--wg-surface-muted); color: var(--wg-text-muted); } .paused { background: #e0e7ff; color: #4338ca; }
    :global(:root.dark) .healthy { background: #14532d; color: #bbf7d0; } :global(:root.dark) .degraded { background: #78350f; color: #fde68a; } :global(:root.dark) .danger { background: #7f1d1d; color: #fecaca; } :global(:root.dark) .paused { background: #312e81; color: #c7d2fe; }
</style>
