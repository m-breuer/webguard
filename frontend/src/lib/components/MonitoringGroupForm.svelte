<script lang="ts">
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import Button from "$lib/components/Button.svelte";
    import Checkbox from "$lib/components/Checkbox.svelte";
    import Field from "$lib/components/Field.svelte";
    import Input from "$lib/components/Input.svelte";
    import Textarea from "$lib/components/Textarea.svelte";

    interface Assignment { id: string; name: string; target: string; }
    interface Group { id: string; name: string; description: string | null; assignments: Assignment[]; }
    interface Props {
        action: string;
        method: "POST" | "PATCH";
        assignments: Assignment[];
        group?: Group | null;
        onSuccess: () => void;
    }

    let { action, method, assignments, group = null, onSuccess }: Props = $props();
    let selectedMonitoringIds = $state<string[]>([]);
    let submitting = $state(false);
    let error = $state("");
    let errors = $state<Record<string, string[]>>({});

    $effect(() => {
        selectedMonitoringIds = group?.assignments.map((monitoring) => monitoring.id) ?? [];
    });

    function errorFor(field: string): string | undefined { return errors[field]?.[0]; }

    async function submit(event: SubmitEvent): Promise<void> {
        event.preventDefault();

        if (submitting) {
            return;
        }

        submitting = true;
        error = "";
        errors = {};

        try {
            await requestFirstPartyApi(action, { body: new FormData(event.currentTarget as HTMLFormElement), method });
            await onSuccess();
        } catch (exception) {
            if (exception instanceof FirstPartyApiError) {
                error = exception.message;
                errors = exception.errors;
            } else {
                error = "The monitoring group could not be saved.";
            }
        } finally {
            submitting = false;
        }
    }
</script>

<form class="grid gap-5" onsubmit={submit} novalidate>
    <Field label="Name" error={errorFor("name")} required><Input name="name" value={group?.name ?? ""} required /></Field>
    <Field label="Description" error={errorFor("description")}><Textarea class="min-h-28" name="description" value={group?.description ?? ""} /></Field>
    <fieldset class="grid gap-2"><legend class="text-sm font-bold">Private monitorings</legend><p class="text-sm leading-6 text-wg-text-muted">Groups can contain monitorings that you privately own.</p><div class="max-h-56 overflow-y-auto rounded-lg border border-wg-border p-3">{#if assignments.length > 0}{#each assignments as monitoring}<label class="flex min-h-10 items-center gap-3 border-b border-wg-border py-2 text-sm last:border-b-0"><Checkbox name="monitoring_ids[]" value={monitoring.id} bind:group={selectedMonitoringIds} /><span class="min-w-0"><span class="block font-bold">{monitoring.name}</span><span class="block truncate text-wg-text-muted">{monitoring.target}</span></span></label>{/each}{:else}<p class="text-sm text-wg-text-muted">No private monitorings are available.</p>{/if}</div>{#if errorFor("monitoring_ids")}<p class="text-sm text-wg-danger">{errorFor("monitoring_ids")}</p>{/if}</fieldset>
    {#if error}<p class="text-sm font-bold text-wg-danger" role="alert">{error}</p>{/if}
    <Button type="submit" loading={submitting}>{group ? "Save group" : "Create group"}</Button>
</form>
