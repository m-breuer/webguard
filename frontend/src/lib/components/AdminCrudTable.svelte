<script lang="ts">
    import { invalidateAll } from "$app/navigation";
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import Button from "$lib/components/Button.svelte";
    import DataTable from "$lib/components/DataTable.svelte";
    import Dialog from "$lib/components/Dialog.svelte";
    import EmptyState from "$lib/components/EmptyState.svelte";

    type Item = Record<string, unknown>;
    interface Column { key: string; label: string; format?: (value: unknown, item: Item) => string; }
    interface Field { name: string; label: string; type?: "text" | "email" | "password" | "number" | "select" | "checkbox"; required?: boolean; options?: Array<{ value: string; label: string }>; }
    interface Props { title: string; description: string; singular: string; endpoint: string; items: Item[]; columns: Column[]; fields: Field[]; createDefaults?: Item; itemLabel?: (item: Item) => string; extraActions?: (item: Item) => Array<{ label: string; endpoint: string }>; }

    let { title, description, singular, endpoint, items, columns, fields, createDefaults = {}, itemLabel = (item) => String(item.id), extraActions = () => [] }: Props = $props();
    let dialogOpen = $state(false);
    let editing = $state<Item | null>(null);
    let error = $state("");
    let saving = $state(false);

    function openCreate(): void { editing = null; error = ""; dialogOpen = true; }
    function openEdit(item: Item): void { editing = item; error = ""; dialogOpen = true; }
    function value(item: Item | null, field: Field): string { const raw = item?.[field.name] ?? createDefaults[field.name] ?? ""; return String(raw); }
    function format(column: Column, item: Item): string { return column.format ? column.format(item[column.key], item) : String(item[column.key] ?? "—"); }

    async function submit(event: SubmitEvent): Promise<void> {
        event.preventDefault();
        saving = true; error = "";
        const form = event.currentTarget as HTMLFormElement;
        const body = new FormData(form);
        for (const field of fields.filter((field) => field.type === "checkbox")) {
            body.set(field.name, (form.elements.namedItem(field.name) as HTMLInputElement).checked ? "1" : "0");
        }
        try {
            await requestFirstPartyApi(editing ? `${endpoint}/${editing.id}` : endpoint, { method: editing ? "PATCH" : "POST", body });
            dialogOpen = false; editing = null; await invalidateAll();
        } catch (exception) { error = exception instanceof FirstPartyApiError ? exception.message : `The ${singular} could not be saved.`; } finally { saving = false; }
    }
    async function remove(item: Item): Promise<void> {
        if (!globalThis.confirm(`Delete ${itemLabel(item)}? This cannot be undone.`)) return;
        error = "";
        try { await requestFirstPartyApi(`${endpoint}/${item.id}`, { method: "DELETE" }); await invalidateAll(); }
        catch (exception) { error = exception instanceof FirstPartyApiError ? exception.message : `The ${singular} could not be deleted.`; }
    }
    async function action(item: Item, mutation: { label: string; endpoint: string }): Promise<void> {
        error = "";
        try { await requestFirstPartyApi(mutation.endpoint, { method: "POST" }); await invalidateAll(); }
        catch (exception) { error = exception instanceof FirstPartyApiError ? exception.message : `The ${singular} could not be updated.`; }
    }
</script>

<section>
    <header class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row"><div><p class="m-0 text-[0.8125rem] font-extrabold tracking-[0.1em] text-wg-accent uppercase">Administration</p><h1 class="mt-2 text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">{title}</h1><p class="mt-3 leading-6 text-wg-text-muted">{description}</p></div><Button onclick={openCreate}>Create {singular}</Button></header>
    {#if error}<p class="mb-5 text-sm font-bold text-wg-danger" role="alert">{error}</p>{/if}
    {#if items.length === 0}<EmptyState title={`No ${title.toLowerCase()} yet`} description={`Create a ${singular} to get started.`} />{:else}<DataTable caption={title}><thead><tr>{#each columns as column}<th>{column.label}</th>{/each}<th>Actions</th></tr></thead><tbody>{#each items as item (String(item.id))}<tr>{#each columns as column}<td>{format(column, item)}</td>{/each}<td><div class="flex flex-wrap gap-2"><button class="min-h-9 rounded-lg border border-wg-border px-3 text-sm font-bold" type="button" onclick={() => openEdit(item)}>Edit</button>{#each extraActions(item) as mutation}<button class="min-h-9 rounded-lg border border-wg-border px-3 text-sm font-bold" type="button" onclick={() => action(item, mutation)}>{mutation.label}</button>{/each}<button class="min-h-9 rounded-lg border border-red-300 px-3 text-sm font-bold text-wg-danger" type="button" onclick={() => remove(item)}>Delete</button></div></td></tr>{/each}</tbody></DataTable>{/if}
</section>

<Dialog bind:open={dialogOpen} title={editing ? `Edit ${singular}` : `Create ${singular}`}><form class="grid gap-4" onsubmit={submit} novalidate>{#each fields as field}<label class="grid gap-1.5 text-sm font-bold"><span>{field.label}</span>{#if field.type === "select"}<select class="min-h-11 rounded-xl border border-wg-border bg-wg-surface px-3" name={field.name} value={value(editing, field)} required={field.required && !editing}>{#each field.options ?? [] as option}<option value={option.value}>{option.label}</option>{/each}</select>{:else if field.type === "checkbox"}<input class="size-5 accent-wg-accent" name={field.name} type="checkbox" checked={value(editing, field) === "true" || value(editing, field) === "1"} />{:else}<input class="min-h-11 rounded-xl border border-wg-border bg-wg-surface px-3" name={field.name} type={field.type ?? "text"} value={value(editing, field)} required={field.required && (!editing || field.type !== "password")} />{/if}</label>{/each}{#if error}<p class="text-sm font-bold text-wg-danger" role="alert">{error}</p>{/if}<Button type="submit" loading={saving}>{editing ? "Save changes" : `Create ${singular}`}</Button></form></Dialog>
