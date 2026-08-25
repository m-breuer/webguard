<script lang="ts">
    import { goto } from "$app/navigation";
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import Card from "$lib/components/Card.svelte";
    import MonitoringAnalytics from "$lib/components/MonitoringAnalytics.svelte";
    import MonitoringTypeDiagnostics from "$lib/components/MonitoringTypeDiagnostics.svelte";
    import StatusBadge from "$lib/components/StatusBadge.svelte";
    import type { MonitoringDetailData, MonitoringSummary } from "$lib/api/monitoring";

    interface Props { data: { monitoring: MonitoringSummary; detail: MonitoringDetailData }; }
    let { data }: Props = $props();
    let deleting = $state(false);
    let error = $state("");

    function statusTone(): "healthy" | "degraded" | "danger" | "neutral" | "paused" {
        if (data.monitoring.lifecycle_status === "paused") return "paused";
        if (data.monitoring.latest_check?.status === "up") return "healthy";
        if (data.monitoring.latest_check?.status === "down") return "danger";
        if (data.monitoring.latest_check?.status === "unknown") return "degraded";

        return "neutral";
    }

    async function remove(): Promise<void> {
        if (deleting) return;

        if (!window.confirm(`Delete ${data.monitoring.name}? This cannot be undone.`)) return;

        deleting = true;
        error = "";
        try {
            await requestFirstPartyApi(`/api/v1/internal/ui/monitorings/${data.monitoring.id}`, { method: "DELETE" });
            await goto("/monitorings");
        } catch (exception) {
            error = exception instanceof FirstPartyApiError ? exception.message : "The monitoring could not be deleted.";
        } finally {
            deleting = false;
        }
    }
</script>

<svelte:head><title>{data.monitoring.name} | WebGuard</title></svelte:head>

<main class="mx-auto w-[min(64rem,calc(100%_-_2rem))] py-6 sm:py-12">
    <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="m-0 text-[0.8125rem] font-extrabold tracking-[0.1em] text-wg-accent uppercase">Monitoring detail</p><h1 class="mt-2 text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">{data.monitoring.name}</h1><p class="mt-3 break-all leading-6 text-wg-text-muted">{data.monitoring.target}</p></div>{#if data.monitoring.can_manage}<div class="flex flex-wrap gap-3"><a class="inline-flex min-h-11 items-center rounded-xl border border-wg-border px-4 py-2.5 text-sm font-bold text-wg-text no-underline" href={`/monitorings/${data.monitoring.id}/edit`}>Edit</a><button class="min-h-11 rounded-xl bg-wg-danger px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60" type="button" disabled={deleting} onclick={remove}>Delete</button></div>{/if}</header>

    <section class="grid gap-6 lg:grid-cols-2"><Card title="Current state" description="Latest result and lifecycle state."><div class="grid gap-4"><StatusBadge tone={statusTone()} label={data.monitoring.lifecycle_status === "paused" ? "Paused" : data.monitoring.latest_check?.status ?? "Waiting for results"} />{#if data.monitoring.latest_check}<dl class="grid grid-cols-2 gap-4"><div><dt class="text-sm text-wg-text-muted">Response time</dt><dd class="mt-1 text-xl font-extrabold">{data.monitoring.latest_check.response_time_ms === null ? "—" : `${Math.round(data.monitoring.latest_check.response_time_ms)} ms`}</dd></div><div><dt class="text-sm text-wg-text-muted">Checked at</dt><dd class="mt-1 text-sm font-bold">{data.monitoring.latest_check.checked_at ?? "—"}</dd></div></dl>{:else}<p class="text-sm leading-6 text-wg-text-muted">Results will appear after the first monitoring cycle. This can take up to the configured check interval.</p>{/if}</div></Card><Card title="Configuration" description="Monitoring scope and operational context."><dl class="grid gap-4"><div><dt class="text-sm text-wg-text-muted">Type</dt><dd class="mt-1 font-bold">{data.monitoring.type ?? "—"}</dd></div><div><dt class="text-sm text-wg-text-muted">Groups</dt><dd class="mt-1 font-bold">{data.monitoring.groups.length === 0 ? "No groups" : data.monitoring.groups.map((group) => group.name).join(", ")}</dd></div><div><dt class="text-sm text-wg-text-muted">Maintenance</dt><dd class="mt-1 font-bold">{data.monitoring.maintenance.has_recurring_window ? "Recurring window configured" : "No recurring window"}</dd></div>{#if data.monitoring.open_incident}<div><dt class="text-sm text-wg-text-muted">Incident</dt><dd class="mt-1"><StatusBadge tone="danger" label="Open incident" /></dd></div>{/if}</dl></Card></section>
    {#if data.monitoring.latest_check === null && data.monitoring.initial_results_wait_minutes !== null && data.monitoring.initial_results_wait_minutes !== undefined}
        <p class="mt-6 rounded-xl border border-wg-border bg-wg-surface-muted p-4 text-sm leading-6 text-wg-text-muted">The first monitoring results can take up to {data.monitoring.initial_results_wait_minutes} minutes, based on the configured check interval.</p>
    {/if}
    <MonitoringAnalytics detail={data.detail} />
    <MonitoringTypeDiagnostics detail={data.detail} />
    {#if error}<p class="mt-6 text-sm font-bold text-wg-danger" role="alert">{error}</p>{/if}
</main>
