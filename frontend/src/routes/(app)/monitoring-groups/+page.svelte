<script lang="ts">
    import { invalidateAll } from "$app/navigation";
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import Button from "$lib/components/Button.svelte";
    import Card from "$lib/components/Card.svelte";
    import Dialog from "$lib/components/Dialog.svelte";
    import EmptyState from "$lib/components/EmptyState.svelte";
    import MonitoringGroupForm from "$lib/components/MonitoringGroupForm.svelte";
    import type { MonitoringAssignment, MonitoringGroup } from "./+page.server";

    interface Props { data: { groups: { data: MonitoringGroup[] }; assignments: { data: MonitoringAssignment[] } }; }
    let { data }: Props = $props();
    let createOpen = $state(false);
    let editOpen = $state(false);
    let editing = $state<MonitoringGroup | null>(null);
    let actionError = $state("");

    async function reload(): Promise<void> {
        createOpen = false;
        editOpen = false;
        editing = null;
        await invalidateAll();
    }

    async function edit(group: MonitoringGroup): Promise<void> {
        actionError = "";
        try {
            editing = (await requestFirstPartyApi<MonitoringGroup>(`/api/v1/internal/ui/monitoring-groups/${group.id}`)).data;
            editOpen = true;
        } catch (exception) {
            actionError = exception instanceof FirstPartyApiError ? exception.message : "The monitoring group could not be loaded.";
        }
    }

    async function remove(group: MonitoringGroup): Promise<void> {
        if (!window.confirm(`Delete ${group.name}? Monitorings will not be deleted.`)) return;
        actionError = "";
        try {
            await requestFirstPartyApi(`/api/v1/internal/ui/monitoring-groups/${group.id}`, { method: "DELETE" });
            await reload();
        } catch (exception) {
            actionError = exception instanceof FirstPartyApiError ? exception.message : "The monitoring group could not be deleted.";
        }
    }
</script>

<svelte:head><title>Monitoring groups | WebGuard</title></svelte:head>

<main class="mx-auto w-[min(70rem,calc(100%_-_2rem))] py-6 sm:py-12">
    <header class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row"><div><p class="m-0 text-[0.8125rem] font-extrabold tracking-[0.1em] text-wg-accent uppercase">Operations</p><h1 class="mt-2 text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">Monitoring groups</h1><p class="mt-3 leading-6 text-wg-text-muted">Organize privately owned monitorings into reusable groups.</p></div><Button onclick={() => (createOpen = true)}>Create group</Button></header>
    {#if actionError}<p class="mb-6 text-sm font-bold text-wg-danger" role="alert">{actionError}</p>{/if}
    {#if data.groups.data.length === 0}<EmptyState title="No monitoring groups yet" description="Create a group to organize related private monitorings." />{:else}<section class="grid grid-cols-[repeat(auto-fit,minmax(17rem,1fr))] gap-4">{#each data.groups.data as group (group.id)}<Card title={group.name} description={group.description ?? "No description provided."}>{#snippet actions()}<span class="text-sm font-bold text-wg-text-muted">{group.assignable_monitoring_count} monitorings</span>{/snippet}<div class="flex flex-wrap gap-3"><button class="min-h-10 rounded-xl border border-wg-border px-3 text-sm font-bold text-wg-text" type="button" onclick={() => edit(group)}>Edit</button><button class="min-h-10 rounded-xl border border-red-300 px-3 text-sm font-bold text-wg-danger" type="button" onclick={() => remove(group)}>Delete</button></div></Card>{/each}</section>{/if}
</main>

<Dialog bind:open={createOpen} title="Create monitoring group" description="Assign private monitorings now or update the group later."><MonitoringGroupForm action="/api/v1/internal/ui/monitoring-groups" method="POST" assignments={data.assignments.data} onSuccess={reload} /></Dialog>
<Dialog bind:open={editOpen} title="Edit monitoring group" description="Update the group and its private monitoring assignments.">{#if editing}<MonitoringGroupForm action={`/api/v1/internal/ui/monitoring-groups/${editing.id}`} method="PATCH" group={editing} assignments={data.assignments.data} onSuccess={reload} />{/if}</Dialog>
