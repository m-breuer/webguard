<script lang="ts">
    import type { MonitoringDetailData } from "$lib/api/monitoring";
    import Card from "$lib/components/Card.svelte";

    interface Props { detail: MonitoringDetailData; }
    let { detail }: Props = $props();
    const latestTelemetry = $derived(detail.server_health_telemetry?.data.at(-1) ?? null);

    function percentage(value: number | null): string {
        return value === null ? "—" : `${value.toFixed(1)}%`;
    }
</script>

{#if detail.server_health_telemetry}
    <section class="mt-6"><Card title="Monitoring diagnostics" description="Type-specific results and thresholds."><div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        {#if detail.server_health_telemetry}<div class="rounded-xl border border-wg-border bg-wg-surface-muted p-4"><h3 class="font-bold">Server health</h3><p class="mt-1 text-sm text-wg-text-muted">Latest report and configured thresholds.</p>{#if latestTelemetry}<dl class="mt-4 grid grid-cols-3 gap-3 text-sm"><div><dt class="text-wg-text-muted">CPU</dt><dd class="mt-1 font-bold">{percentage(latestTelemetry.cpu_usage_percent)}</dd><dd class="text-xs text-wg-text-muted">Limit {detail.server_health_telemetry.thresholds.cpu_usage_percent}%</dd></div><div><dt class="text-wg-text-muted">RAM</dt><dd class="mt-1 font-bold">{percentage(latestTelemetry.ram_usage_percent)}</dd><dd class="text-xs text-wg-text-muted">Limit {detail.server_health_telemetry.thresholds.ram_usage_percent}%</dd></div><div><dt class="text-wg-text-muted">Storage</dt><dd class="mt-1 font-bold">{percentage(latestTelemetry.storage_usage_percent)}</dd><dd class="text-xs text-wg-text-muted">Limit {detail.server_health_telemetry.thresholds.storage_usage_percent}%</dd></div></dl>{:else}<p class="mt-4 text-sm text-wg-text-muted">No server-health reports have been received yet.</p>{/if}</div>{/if}
    </div></Card></section>
{/if}
