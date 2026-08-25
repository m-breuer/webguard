<script lang="ts">
    import { goto } from "$app/navigation";
    import { requestFirstPartyApi, FirstPartyApiError } from "$lib/api/client";
    import type { MonitoringFormOptions, MonitoringMutationResult, MonitoringType } from "$lib/api/monitoring";
    import Button from "$lib/components/Button.svelte";
    import Field from "$lib/components/Field.svelte";

    interface Props {
        options: MonitoringFormOptions;
        action: string;
        method: "POST" | "PATCH";
    }

    let { options, action, method }: Props = $props();
    const initial = initialState();
    const monitoring = initial.monitoring;
    let type = $state<MonitoringType>(initial.type);
    let selectedLocations = $state<string[]>(initial.locations);
    let selectedGroups = $state<string[]>(initial.groups);
    let submitting = $state(false);
    let message = $state("");
    let errors = $state<Record<string, string[]>>({});

    const httpTypes = $derived(type === "http" || type === "keyword");
    const generatedTarget = $derived(type === "heartbeat" || type === "server_health");

    function initialState(): { monitoring: MonitoringFormOptions["monitoring"]; type: MonitoringType; locations: string[]; groups: string[] } {
        const configuredMonitoring = options.monitoring;

        return {
            monitoring: configuredMonitoring,
            type: configuredMonitoring?.type ?? "http",
            locations: configuredMonitoring?.preferred_locations ?? options.locations.slice(0, 1),
            groups: configuredMonitoring?.group_ids ?? [],
        };
    }

    function errorFor(name: string): string | undefined {
        return errors[name]?.[0];
    }

    async function submit(event: SubmitEvent): Promise<void> {
        event.preventDefault();

        if (submitting) {
            return;
        }

        submitting = true;
        message = "";
        errors = {};

        try {
            const form = event.currentTarget as HTMLFormElement;
            const response = await requestFirstPartyApi<MonitoringMutationResult>(action, {
                body: new FormData(form),
                method,
            });

            await goto(`/monitorings/${response.data.id}`);
        } catch (error) {
            if (error instanceof FirstPartyApiError) {
                errors = error.errors;
                message = error.message;
            } else {
                message = "The monitoring could not be saved. Please try again.";
            }
        } finally {
            submitting = false;
        }
    }
</script>

<form class="grid gap-6" onsubmit={submit} novalidate>
    <section class="grid gap-4">
        <h2 class="text-lg font-bold">Basic configuration</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <Field label="Monitoring type" error={errorFor("type")} required>
                {#if monitoring}
                    <input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface-muted px-3 py-[0.65rem] text-wg-text" value={type} readonly />
                    <input name="type" type="hidden" value={type} />
                {:else}
                    <select class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="type" bind:value={type}>
                        {#each options.types as option}<option value={option}>{option.replaceAll("_", " ")}</option>{/each}
                    </select>
                {/if}
            </Field>
            <Field label="Lifecycle" error={errorFor("status")} required>
                <select class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="status" value={monitoring?.status ?? "active"}>
                    <option value="active">Active</option><option value="paused">Paused</option>
                </select>
            </Field>
        </div>
        <Field label="Name" error={errorFor("name")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="name" value={monitoring?.name ?? ""} required /></Field>
        {#if generatedTarget}
            <p class="rounded-xl border border-dashed border-wg-border bg-wg-surface-muted p-3 text-sm text-wg-text-muted">The endpoint is generated securely after this monitoring is saved.</p>
        {:else}
            <Field label="Target" error={errorFor("target")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="target" value={monitoring?.target ?? ""} required /></Field>
        {/if}
    </section>

    <section class="grid gap-4 border-t border-wg-border pt-6">
        <h2 class="text-lg font-bold">Check settings</h2>
        {#if type === "port"}
            <Field label="Port" error={errorFor("port")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="port" type="number" min="1" max="65535" value={monitoring?.port ?? ""} required /></Field>
        {:else if type === "dns_record"}
            <div class="grid gap-4 sm:grid-cols-2"><Field label="DNS record type" error={errorFor("dns_record_type")} required><select class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="dns_record_type" value={monitoring?.dns_record_type ?? "A"}>{#each ["A", "AAAA", "CNAME", "MX", "TXT", "NS", "SOA", "CAA"] as recordType}<option value={recordType}>{recordType}</option>{/each}</select></Field><Field label="Expected values" error={errorFor("dns_expected_values")} required><textarea class="min-h-28 w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="dns_expected_values" required>{monitoring?.dns_expected_values?.join("\n") ?? ""}</textarea></Field></div>
        {:else if type === "heartbeat"}
            <div class="grid gap-4 sm:grid-cols-2"><Field label="Expected interval (minutes)" error={errorFor("heartbeat_interval_minutes")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="heartbeat_interval_minutes" type="number" min="1" value={monitoring?.heartbeat_interval_minutes ?? 5} required /></Field><Field label="Grace period (minutes)" error={errorFor("heartbeat_grace_minutes")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="heartbeat_grace_minutes" type="number" min="0" value={monitoring?.heartbeat_grace_minutes ?? 5} required /></Field></div>
        {:else if type === "server_health"}
            <div class="grid gap-4 sm:grid-cols-2"><Field label="CPU threshold (%)" error={errorFor("server_health_cpu_threshold_percent")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="server_health_cpu_threshold_percent" type="number" min="1" max="100" value={monitoring?.server_health_cpu_threshold_percent ?? 90} required /></Field><Field label="RAM threshold (%)" error={errorFor("server_health_ram_threshold_percent")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="server_health_ram_threshold_percent" type="number" min="1" max="100" value={monitoring?.server_health_ram_threshold_percent ?? 90} required /></Field><Field label="Storage threshold (%)" error={errorFor("server_health_storage_threshold_percent")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="server_health_storage_threshold_percent" type="number" min="1" max="100" value={monitoring?.server_health_storage_threshold_percent ?? 90} required /></Field><Field label="Report interval (minutes)" error={errorFor("server_health_report_interval_minutes")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="server_health_report_interval_minutes" type="number" min="1" value={monitoring?.server_health_report_interval_minutes ?? 1} required /></Field><Field label="Grace period (minutes)" error={errorFor("server_health_grace_minutes")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="server_health_grace_minutes" type="number" min="0" value={monitoring?.server_health_grace_minutes ?? 5} required /></Field></div>
        {/if}
        {#if httpTypes}
            <div class="grid gap-4 sm:grid-cols-2"><Field label="HTTP method" error={errorFor("http_method")}><select class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="http_method" value={monitoring?.http_method ?? "get"}>{#each ["get", "post", "put", "patch", "delete"] as httpMethod}<option value={httpMethod}>{httpMethod.toUpperCase()}</option>{/each}</select></Field><Field label="Timeout (seconds)" error={errorFor("timeout")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="timeout" type="number" min="1" max="60" value={monitoring?.timeout ?? 5} required /></Field><Field label="Expected HTTP statuses" error={errorFor("expected_http_statuses")}><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="expected_http_statuses" value={monitoring?.expected_http_statuses ?? "200-299"} /></Field>{#if type === "keyword"}<Field label="Required keyword" error={errorFor("keyword")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="keyword" value={monitoring?.keyword ?? ""} required /></Field>{/if}</div>
            {#if monitoring}<label class="flex items-center gap-2 text-sm text-wg-text-muted"><input name="clear_auth_password" type="checkbox" /> Remove stored HTTP basic-auth password</label>{/if}
        {/if}
    </section>

    <section class="grid gap-4 border-t border-wg-border pt-6">
        <h2 class="text-lg font-bold">Assignment and alerts</h2>
        {#if !monitoring && options.teams.length > 0}<Field label="Owner"><select class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="team_id"><option value="">Private monitoring</option>{#each options.teams as team}<option value={team.id}>Team: {team.name}</option>{/each}</select></Field>{/if}
        <fieldset class="grid gap-2"><legend class="text-sm font-bold">Monitoring locations</legend>{#each options.locations as location}<label class="flex items-center gap-2 text-sm"><input name="preferred_locations[]" type="checkbox" value={location} bind:group={selectedLocations} />{location}</label>{/each}{#if errorFor("preferred_locations")}<p class="text-sm text-wg-danger">{errorFor("preferred_locations")}</p>{/if}</fieldset>
        {#if !monitoring || monitoring.can_assign_groups}<fieldset class="grid gap-2"><legend class="text-sm font-bold">Groups</legend>{#each options.groups as group}<label class="flex items-center gap-2 text-sm"><input name="group_ids[]" type="checkbox" value={group.id} bind:group={selectedGroups} />{group.name}</label>{/each}</fieldset>{/if}
        <div class="grid gap-4 sm:grid-cols-2"><Field label="Failure confirmation attempts" error={errorFor("failure_confirmation_threshold")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="failure_confirmation_threshold" type="number" min="1" max="10" value={monitoring?.failure_confirmation_threshold ?? 2} required /></Field><Field label="SSL warning days" error={errorFor("ssl_expiry_warning_days")} required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="ssl_expiry_warning_days" type="number" min="1" max="365" value={monitoring?.ssl_expiry_warning_days ?? 7} required /></Field></div>
        <label class="flex items-center gap-2 text-sm font-bold"><input name="notification_on_failure" type="checkbox" checked={monitoring?.notification_on_failure ?? true} /> Notify on failure</label>
    </section>

    {#if message}<p class="text-sm font-bold text-wg-danger" role="alert">{message}</p>{/if}
    <div class="flex flex-wrap gap-3"><Button type="submit" loading={submitting}>{monitoring ? "Save monitoring" : "Create monitoring"}</Button><a class="inline-flex min-h-11 items-center rounded-xl border border-wg-border px-4 py-2.5 text-sm font-bold text-wg-text no-underline" href={monitoring ? `/monitorings/${monitoring.id}` : "/monitorings"}>Cancel</a></div>
</form>
