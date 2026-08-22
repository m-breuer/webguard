<script lang="ts">
    import type { MonitoringDetailData } from "$lib/api/monitoring";
    import Card from "$lib/components/Card.svelte";
    import StatusBadge from "$lib/components/StatusBadge.svelte";

    interface Props { detail: MonitoringDetailData; }
    let { detail }: Props = $props();
    const latestTelemetry = $derived(detail.server_health_telemetry?.data.at(-1) ?? null);

    function timestamp(value: string | null): string {
        return value ? new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "—";
    }

    function percentage(value: number | null): string {
        return value === null ? "—" : `${value.toFixed(1)}%`;
    }
</script>

{#if detail.ssl || detail.domain || detail.server_health_telemetry}
    <section class="mt-6"><Card title="Monitoring diagnostics" description="Type-specific results and thresholds."><div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        {#if detail.ssl}<div class="rounded-xl border border-wg-border bg-wg-surface-muted p-4"><div class="flex items-center justify-between gap-3"><h3 class="font-bold">SSL certificate</h3><StatusBadge tone={detail.ssl.valid ? "healthy" : "danger"} label={detail.ssl.valid ? "Valid" : "Invalid"} /></div><dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-wg-text-muted">Expires</dt><dd class="mt-1 font-bold">{timestamp(detail.ssl.expiration)}</dd></div><div><dt class="text-wg-text-muted">Issuer</dt><dd class="mt-1 font-bold">{detail.ssl.issuer ?? "—"}</dd></div></dl></div>{/if}
        {#if detail.domain}<div class="rounded-xl border border-wg-border bg-wg-surface-muted p-4"><div class="flex items-center justify-between gap-3"><h3 class="font-bold">Domain registration</h3><StatusBadge tone={detail.domain.valid ? "healthy" : "danger"} label={detail.domain.valid ? "Valid" : "Invalid"} /></div><dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-wg-text-muted">Expires</dt><dd class="mt-1 font-bold">{timestamp(detail.domain.expires_at)}</dd></div><div><dt class="text-wg-text-muted">Registrar</dt><dd class="mt-1 font-bold">{detail.domain.registrar ?? "—"}</dd></div></dl></div>{/if}
        {#if detail.server_health_telemetry}<div class="rounded-xl border border-wg-border bg-wg-surface-muted p-4"><h3 class="font-bold">Server health</h3><p class="mt-1 text-sm text-wg-text-muted">Latest report and configured thresholds.</p>{#if latestTelemetry}<dl class="mt-4 grid grid-cols-3 gap-3 text-sm"><div><dt class="text-wg-text-muted">CPU</dt><dd class="mt-1 font-bold">{percentage(latestTelemetry.cpu_usage_percent)}</dd><dd class="text-xs text-wg-text-muted">Limit {detail.server_health_telemetry.thresholds.cpu_usage_percent}%</dd></div><div><dt class="text-wg-text-muted">RAM</dt><dd class="mt-1 font-bold">{percentage(latestTelemetry.ram_usage_percent)}</dd><dd class="text-xs text-wg-text-muted">Limit {detail.server_health_telemetry.thresholds.ram_usage_percent}%</dd></div><div><dt class="text-wg-text-muted">Storage</dt><dd class="mt-1 font-bold">{percentage(latestTelemetry.storage_usage_percent)}</dd><dd class="text-xs text-wg-text-muted">Limit {detail.server_health_telemetry.thresholds.storage_usage_percent}%</dd></div></dl>{:else}<p class="mt-4 text-sm text-wg-text-muted">No server-health reports have been received yet.</p>{/if}</div>{/if}
    </div></Card></section>
{/if}
