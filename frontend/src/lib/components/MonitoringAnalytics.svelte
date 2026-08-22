<script lang="ts">
    import type { MonitoringDetailData } from "$lib/api/monitoring";
    import Card from "$lib/components/Card.svelte";
    import DataTable from "$lib/components/DataTable.svelte";
    import StatusBadge from "$lib/components/StatusBadge.svelte";

    interface Props { detail: MonitoringDetailData; }
    let { detail }: Props = $props();

    const points = $derived(detail.response_times.data.filter((point) => point.avg !== null));
    const chartMaximum = $derived(Math.max(...points.map((point) => point.avg ?? 0), 1));
    const calendarDays = $derived(
        Object.values(detail.uptime_calendar)
            .flatMap((month) => month.days)
            .filter((day) => new Date(day.date) <= new Date())
            .slice(-28),
    );

    function chartPoints(): string {
        if (points.length === 0) return "";

        return points.map((point, index) => {
            const x = points.length === 1 ? 50 : (index / (points.length - 1)) * 100;
            const y = 100 - ((point.avg ?? 0) / chartMaximum) * 92 - 4;

            return `${x},${y}`;
        }).join(" ");
    }

    function timestamp(value: string | null): string {
        return value ? new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "—";
    }

    function duration(incident: { down_at: string; up_at: string | null }): string {
        const end = incident.up_at ? new Date(incident.up_at) : new Date();
        const minutes = Math.max(0, Math.round((end.getTime() - new Date(incident.down_at).getTime()) / 60_000));

        return minutes >= 60 ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : `${minutes}m`;
    }

    function checkTone(status: "up" | "down" | "unknown"): "healthy" | "danger" | "degraded" {
        return status === "up" ? "healthy" : status === "down" ? "danger" : "degraded";
    }

    function uptimeTone(uptime: number | null): string {
        if (uptime === null) return "bg-wg-surface-muted";
        if (uptime >= 99.9) return "bg-green-500";
        if (uptime >= 95) return "bg-amber-400";

        return "bg-red-500";
    }
</script>

<section class="mt-6 grid gap-6 xl:grid-cols-2">
    <Card title="Response time" description="Average response time across the last 30 days.">
        {#if points.length > 0}
            <div class="flex items-end justify-between gap-4"><div><p class="text-sm text-wg-text-muted">Average</p><p class="mt-1 text-3xl font-extrabold">{Math.round(detail.response_times.aggregated.avg ?? 0)} ms</p></div><dl class="grid grid-cols-2 gap-4 text-right text-sm"><div><dt class="text-wg-text-muted">Fastest</dt><dd class="mt-1 font-bold">{Math.round(detail.response_times.aggregated.min ?? 0)} ms</dd></div><div><dt class="text-wg-text-muted">Slowest</dt><dd class="mt-1 font-bold">{Math.round(detail.response_times.aggregated.max ?? 0)} ms</dd></div></dl></div>
            <svg class="mt-6 h-44 w-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Response time trend"><line x1="0" y1="96" x2="100" y2="96" class="stroke-wg-border" stroke-width="1" vector-effect="non-scaling-stroke"></line><polyline points={chartPoints()} fill="none" class="stroke-wg-accent" stroke-width="2" vector-effect="non-scaling-stroke"></polyline></svg>
        {:else}
            <p class="text-sm leading-6 text-wg-text-muted">Response-time data will appear after the monitoring collects results.</p>
        {/if}
    </Card>

    <Card title="Uptime calendar" description="Daily availability across the last 30 days.">
        <div class="grid grid-cols-7 gap-2" aria-label="Uptime calendar">
            {#each calendarDays as day}
                <div class={`aspect-square rounded-md ${uptimeTone(day.uptime_percentage)}`} title={`${timestamp(day.date)}: ${day.uptime_percentage === null ? "No data" : `${day.uptime_percentage.toFixed(2)}% uptime`}`}></div>
            {/each}
            {#if calendarDays.length === 0}<p class="col-span-7 text-sm leading-6 text-wg-text-muted">Daily availability will appear after a full monitoring day.</p>{/if}
        </div>
        {#if detail.availability.has_data}<dl class="mt-6 grid grid-cols-3 gap-3 text-sm"><div><dt class="text-wg-text-muted">Uptime</dt><dd class="mt-1 font-bold">{detail.availability.uptime.percentage?.toFixed(2) ?? "—"}%</dd></div><div><dt class="text-wg-text-muted">Downtime</dt><dd class="mt-1 font-bold">{detail.availability.downtime.percentage?.toFixed(2) ?? "—"}%</dd></div><div><dt class="text-wg-text-muted">Unknown</dt><dd class="mt-1 font-bold">{detail.availability.unknown.percentage?.toFixed(2) ?? "—"}%</dd></div></dl>{/if}
    </Card>
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
    <Card title="Recent checks" description="The most recent ten monitoring results.">
        {#if detail.recent_checks.length > 0}
            <DataTable caption="Recent monitoring checks"><thead><tr><th scope="col">Status</th><th scope="col">Checked at</th><th scope="col">HTTP</th><th scope="col">Response time</th></tr></thead><tbody>{#each detail.recent_checks as check}<tr><td><StatusBadge tone={checkTone(check.status)} label={check.status} /></td><td class="text-sm">{timestamp(check.checked_at)}</td><td>{check.http_status_code ?? "—"}</td><td>{check.response_time === null ? "—" : `${Math.round(check.response_time)} ms`}</td></tr>{/each}</tbody></DataTable>
        {:else}
            <p class="text-sm leading-6 text-wg-text-muted">No checks have been recorded yet.</p>
        {/if}
    </Card>

    <Card title="Incidents" description="Recent interruptions in this monitoring.">
        {#if detail.incidents.length > 0}<ul class="grid gap-4 p-0">{#each detail.incidents as incident}<li class="list-none border-b border-wg-border py-3 first:pt-0 last:border-b-0 last:pb-0"><p class="font-bold">{incident.up_at ? "Resolved incident" : "Open incident"}</p><p class="mt-1 text-sm leading-6 text-wg-text-muted">{timestamp(incident.down_at)} · {duration(incident)}</p></li>{/each}</ul>{:else}<p class="text-sm leading-6 text-wg-text-muted">No incidents in the selected period.</p>{/if}
    </Card>
</section>
