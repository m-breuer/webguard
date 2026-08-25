<script lang="ts">
    import { invalidateAll } from "$app/navigation";
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import type { StatusPage, StatusPageIncident } from "$lib/api/status-pages";
    import Button from "$lib/components/Button.svelte";
    import Card from "$lib/components/Card.svelte";
    import Dialog from "$lib/components/Dialog.svelte";
    import Field from "$lib/components/Field.svelte";
    import StatusBadge from "$lib/components/StatusBadge.svelte";

    interface Props { data: { statusPage: { data: StatusPage }; incidents: { data: StatusPageIncident[] } }; }
    let { data }: Props = $props();
    const statusPage = $derived(data.statusPage.data);
    let publishOpen = $state(false);
    let selectedIncident = $state<StatusPageIncident | null>(null);
    let updatingPublication = $state(false);
    let publishingUpdate = $state(false);
    let error = $state("");
    let message = $state("");

    function dateTime(value: string | null): string {
        return value ? new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "Not recorded";
    }

    async function updatePublication(): Promise<void> {
        if (updatingPublication || publishingUpdate) {
            return;
        }

        updatingPublication = true;
        error = "";
        message = "";
        try {
            await requestFirstPartyApi(`/api/v1/internal/ui/status-pages/${statusPage.id}/publication`, { body: JSON.stringify({ is_public: !statusPage.publication.is_public }), method: "PATCH" });
            message = statusPage.publication.is_public ? "Status page unpublished." : "Status page published.";
            await invalidateAll();
        } catch (exception) { error = exception instanceof FirstPartyApiError ? exception.message : "The publication state could not be updated."; } finally { updatingPublication = false; }
    }

    async function publishIncidentUpdate(event: SubmitEvent): Promise<void> {
        event.preventDefault();

        if (!selectedIncident || publishingUpdate || updatingPublication) {
            return;
        }

        publishingUpdate = true;
        error = "";
        try {
            await requestFirstPartyApi(`/api/v1/internal/ui/status-pages/${statusPage.id}/incidents/${selectedIncident.id}/updates`, {
                body: new FormData(event.currentTarget as HTMLFormElement),
                headers: { "Idempotency-Key": crypto.randomUUID() },
                method: "POST",
            });
            publishOpen = false;
            selectedIncident = null;
            message = "Incident update published.";
            await invalidateAll();
        } catch (exception) { error = exception instanceof FirstPartyApiError ? exception.message : "The incident update could not be published."; } finally { publishingUpdate = false; }
    }
</script>

<svelte:head><title>{statusPage.name} | Status pages | WebGuard</title></svelte:head>

<main class="mx-auto w-[min(70rem,calc(100%_-_2rem))] py-6 sm:py-12">
    <a class="text-sm font-bold text-wg-accent no-underline" href="/status-pages">← Status pages</a>
    <header class="mt-5 mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row"><div><div class="flex flex-wrap items-center gap-3"><h1 class="text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">{statusPage.name}</h1><StatusBadge tone={statusPage.publication.is_public ? "healthy" : "paused"} label={statusPage.publication.is_public ? "Public" : "Private"} /></div>{#if statusPage.description}<p class="mt-3 max-w-2xl leading-6 text-wg-text-muted">{statusPage.description}</p>{/if}</div><div class="flex flex-wrap gap-3">{#if statusPage.publication.is_public}<a class="inline-flex min-h-11 items-center justify-center rounded-xl border border-wg-border px-4 py-2.5 text-sm font-bold text-wg-text no-underline" href={`/status/${statusPage.id}`} target="_blank" rel="noreferrer">Open public page</a>{/if}<Button variant="secondary" loading={updatingPublication} disabled={publishingUpdate} onclick={updatePublication}>{statusPage.publication.is_public ? "Unpublish" : "Publish"}</Button></div></header>
    {#if message}<p class="mb-6 text-sm font-bold text-green-700 dark:text-green-300" role="status">{message}</p>{/if}{#if error}<p class="mb-6 text-sm font-bold text-wg-danger" role="alert">{error}</p>{/if}
    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]"><Card title="Components" description={`${statusPage.verified_subscriber_count} verified subscribers`}><div class="grid gap-4">{#each statusPage.components as component (component.id)}<article class="border-b border-wg-border pb-4 last:border-b-0"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-bold">{component.name}</h2><span class="text-sm font-bold text-wg-text-muted">{component.monitorings.length} monitorings</span></div>{#if component.description}<p class="mt-1 text-sm leading-5 text-wg-text-muted">{component.description}</p>{/if}{#if component.monitoring_group}<p class="mt-3 text-sm text-wg-text-muted">Monitoring group: <span class="font-bold text-wg-text">{component.monitoring_group.name}</span></p>{/if}<ul class="mt-3 grid gap-2 p-0">{#each component.monitorings as monitoring (monitoring.id)}<li class="list-none rounded-xl bg-wg-surface-muted px-3 py-2 text-sm"><span class="font-bold">{monitoring.name}</span><span class="ml-2 text-wg-text-muted">{monitoring.target}</span></li>{/each}</ul></article>{/each}</div></Card>
        <Card title="Incidents" description="Publish clear updates as incidents are investigated and resolved.">{#if data.incidents.data.length > 0}<div class="grid gap-4">{#each data.incidents.data as incident (incident.id)}<article class="rounded-xl border border-wg-border p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div><div class="flex items-center gap-2"><h2 class="font-bold">{incident.monitoring.name}</h2><StatusBadge tone={incident.lifecycle.state === "open" ? "danger" : "healthy"} label={incident.lifecycle.state} /></div><p class="mt-1 text-sm text-wg-text-muted">Opened {dateTime(incident.lifecycle.opened_at)}</p></div><button class="min-h-10 rounded-xl border border-wg-border px-3 text-sm font-bold text-wg-text" type="button" onclick={() => { selectedIncident = incident; publishOpen = true; }}>Publish update</button></div>{#if incident.readiness.requires_public_update}<p class="mt-3 text-sm font-bold text-amber-700 dark:text-amber-300">This open incident has no public update yet.</p>{/if}{#if incident.updates.length > 0}<ol class="mt-4 grid gap-3 p-0">{#each incident.updates as update (update.id)}<li class="list-none border-l-2 border-wg-accent pl-3"><p class="text-sm font-bold">{update.status}</p><p class="mt-1 text-sm leading-5 text-wg-text-muted">{update.message}</p><p class="mt-1 text-xs text-wg-text-muted">{dateTime(update.published_at)}</p></li>{/each}</ol>{/if}</article>{/each}</div>{:else}<p class="text-sm leading-6 text-wg-text-muted">No incidents are associated with these components.</p>{/if}</Card></section>
</main>

<Dialog bind:open={publishOpen} title="Publish incident update" description={selectedIncident ? `Share an update for ${selectedIncident.monitoring.name}.` : ""}>{#if selectedIncident}<form class="grid gap-5" onsubmit={publishIncidentUpdate} novalidate><Field label="Status" required><select class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="status" required><option value="investigating">Investigating</option><option value="identified">Identified</option><option value="monitoring">Monitoring</option><option value="resolved">Resolved</option></select></Field><Field label="Message" required><textarea class="min-h-32 w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="message" required></textarea></Field><Button type="submit" loading={publishingUpdate}>Publish update</Button></form>{/if}</Dialog>
