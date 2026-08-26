<script lang="ts">
    import { appRoutes } from "$lib/routes";
    import Button from "$lib/components/Button.svelte";
    import DataTable from "$lib/components/DataTable.svelte";
    import EmptyState from "$lib/components/EmptyState.svelte";
    import Input from "$lib/components/Input.svelte";
    import Pagination from "$lib/components/Pagination.svelte";
    import Select from "$lib/components/Select.svelte";
    import StatusBadge from "$lib/components/StatusBadge.svelte";
    import type { MonitoringListResponse, MonitoringSummary } from "$lib/api/monitoring";

    interface Props {
        data: {
            filters: { lifecycleStatus: string; search: string };
            monitorings: MonitoringListResponse;
        };
    }

    let { data }: Props = $props();

    function statusTone(monitoring: MonitoringSummary): "healthy" | "degraded" | "danger" | "neutral" | "paused" {
        if (monitoring.lifecycle_status === "paused") return "paused";
        if (monitoring.latest_check?.status === "up") return "healthy";
        if (monitoring.latest_check?.status === "down") return "danger";
        if (monitoring.latest_check?.status === "unknown") return "degraded";

        return "neutral";
    }

    function statusLabel(monitoring: MonitoringSummary): string {
        if (monitoring.lifecycle_status === "paused") return "Paused";
        if (monitoring.latest_check === null) return "Waiting for results";

        return monitoring.latest_check.status === "up" ? "Operational"
            : monitoring.latest_check.status === "down" ? "Down"
                : "Unknown";
    }

    function checkedAt(monitoring: MonitoringSummary): string {
        if (monitoring.latest_check?.checked_at === null || monitoring.latest_check === null) return "No result yet";

        return new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(monitoring.latest_check.checked_at));
    }

    function paginationHref(page: number): string {
        const params = new URLSearchParams();

        if (page > 1) params.set("page", String(page));
        if (data.filters.search !== "") params.set("search", data.filters.search);
        if (data.filters.lifecycleStatus !== "") params.set("lifecycle_status", data.filters.lifecycleStatus);

        return params.size === 0 ? appRoutes.monitorings : `${appRoutes.monitorings}?${params}`;
    }
</script>

<svelte:head><title>Monitorings | WebGuard</title></svelte:head>

<main class="mx-auto w-[min(76rem,calc(100%_-_2rem))] py-6 sm:py-12">
    <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="m-0 text-[0.8125rem] font-extrabold tracking-[0.1em] text-wg-accent uppercase">Operations</p>
            <h1 class="mt-2 text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">Monitorings</h1>
            <p class="mt-3 max-w-2xl leading-6 text-wg-text-muted">Search visible monitorings and review their latest service state.</p>
        </div>
        <a class="inline-flex min-h-11 items-center justify-center rounded-xl border border-transparent bg-wg-accent px-4 py-2.5 text-sm font-bold tracking-[0.035em] text-wg-accent-contrast no-underline transition hover:bg-wg-accent-strong" href="/monitorings/create">Create monitoring</a>
    </header>

    <form class="mb-6 grid gap-3 rounded-2xl border border-wg-border bg-wg-surface p-4 shadow-wg-surface sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end" method="GET">
        <label class="grid gap-2 text-sm font-bold">
            Search
            <Input name="search" type="search" value={data.filters.search} placeholder="Name or target" />
        </label>
        <label class="grid gap-2 text-sm font-bold">
            Lifecycle
            <Select name="lifecycle_status" value={data.filters.lifecycleStatus}>
                <option value="">All monitorings</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
            </Select>
        </label>
        <Button type="submit">Apply filters</Button>
    </form>

    {#if data.monitorings.data.length === 0}
        <EmptyState title="No monitorings found" description="Adjust your filters or create a monitoring to start collecting availability data.">
            {#snippet action()}<a class="inline-flex min-h-11 items-center justify-center rounded-xl bg-wg-accent px-4 py-2.5 text-sm font-bold text-wg-accent-contrast no-underline" href="/monitorings/create">Create monitoring</a>{/snippet}
        </EmptyState>
    {:else}
        <section class="overflow-hidden rounded-2xl border border-wg-border bg-wg-surface shadow-wg-surface">
            <div class="flex flex-col gap-1 border-b border-wg-border px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <h2 class="text-xl font-bold">Visible monitorings</h2>
                <p class="text-sm text-wg-text-muted">{data.monitorings.meta.total} total</p>
            </div>
            <div class="p-5 sm:p-7">
                <DataTable caption="Visible monitorings">
                    <thead><tr><th>Name</th><th>Target</th><th>Groups</th><th>Latest result</th><th>Status</th></tr></thead>
                    <tbody>
                        {#each data.monitorings.data as monitoring (monitoring.id)}
                            <tr>
                                <td><a class="font-bold text-wg-text no-underline hover:text-wg-accent" href={`/monitorings/${monitoring.id}`}>{monitoring.name}</a><p class="mt-1 text-xs text-wg-text-muted">{monitoring.type ?? "Monitoring"}</p></td>
                                <td><span class="block max-w-64 truncate" title={monitoring.target}>{monitoring.target}</span></td>
                                <td>{monitoring.groups.length === 0 ? "—" : monitoring.groups.map((group) => group.name).join(", ")}</td>
                                <td>{monitoring.latest_check?.response_time_ms === null || monitoring.latest_check === null ? "—" : `${Math.round(monitoring.latest_check.response_time_ms)} ms`}<p class="mt-1 text-xs text-wg-text-muted">{checkedAt(monitoring)}</p></td>
                                <td><div class="flex flex-wrap gap-1"><StatusBadge tone={statusTone(monitoring)} label={statusLabel(monitoring)} />{#if monitoring.open_incident}<StatusBadge tone="danger" label="Incident" />{/if}{#if monitoring.maintenance.has_recurring_window}<StatusBadge tone="neutral" label="Maintenance" />{/if}</div></td>
                            </tr>
                        {/each}
                    </tbody>
                </DataTable>
            </div>
            <div class="border-t border-wg-border px-5 py-4 sm:px-7"><Pagination page={data.monitorings.meta.current_page} pages={data.monitorings.meta.last_page} href={paginationHref} /></div>
        </section>
    {/if}
</main>
