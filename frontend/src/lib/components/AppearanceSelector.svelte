<script lang="ts">
    import { onMount } from "svelte";
    import { applyAppearanceTheme, storedAppearanceTheme } from "$lib/appearance";
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import type { AppearanceTheme } from "$lib/api/models";

    interface Props {
        initialTheme?: AppearanceTheme;
        endpoint?: string;
        compact?: boolean;
    }

    let { initialTheme, endpoint = "/api/v1/internal/ui/appearance", compact = false }: Props = $props();
    let theme = $state<AppearanceTheme>("system");
    let saving = $state(false);
    let error = $state("");

    const options: { value: AppearanceTheme; label: string }[] = [
        { value: "light", label: "Light" },
        { value: "dark", label: "Dark" },
        { value: "system", label: "System" },
    ];

    onMount(() => {
        theme = initialTheme ?? storedAppearanceTheme();
        applyAppearanceTheme(theme);
    });

    async function select(nextTheme: AppearanceTheme): Promise<void> {
        if (saving || nextTheme === theme) {
            return;
        }

        const previousTheme = theme;
        theme = nextTheme;
        error = "";
        applyAppearanceTheme(nextTheme);
        saving = true;

        try {
            const response = await requestFirstPartyApi<{ theme: AppearanceTheme }>(endpoint, {
                body: JSON.stringify({ theme: nextTheme }),
                method: "PATCH",
            });

            theme = response.data.theme;
            applyAppearanceTheme(theme);
        } catch (exception) {
            theme = previousTheme;
            applyAppearanceTheme(previousTheme);
            error = exception instanceof FirstPartyApiError ? exception.message : "Appearance could not be saved.";
        } finally {
            saving = false;
        }
    }
</script>

{#if compact}
    <details class="relative">
        <summary class="grid size-11 cursor-pointer list-none place-items-center rounded-[0.65rem] border border-purple-700 text-xl text-purple-100 [&::-webkit-details-marker]:hidden" aria-label="Appearance" title="Appearance">☼</summary>
        <div class="absolute bottom-[calc(100%+0.5rem)] left-0 z-30 w-48 rounded-xl border border-wg-border bg-wg-surface p-2 shadow-wg-surface" aria-label="Appearance" aria-busy={saving}>
            {@render optionsList()}
        </div>
    </details>
{:else}
    <fieldset class="m-0 border-0 p-0" aria-busy={saving}>
        <legend class="mb-2 text-sm font-bold text-wg-text">Appearance</legend>
        {@render optionsList()}
    </fieldset>
{/if}

{#snippet optionsList()}
    <div class="grid grid-cols-3 overflow-hidden rounded-xl border border-wg-border">
        {#each options as option}
            <button
                type="button"
                class={`min-h-10 border-0 border-r border-wg-border bg-wg-surface text-[0.8125rem] font-bold text-wg-text-muted last:border-r-0 disabled:cursor-wait disabled:opacity-65 ${theme === option.value ? "bg-wg-accent text-wg-accent-contrast" : ""}`}
                aria-pressed={theme === option.value}
                disabled={saving}
                onclick={() => select(option.value)}
            >
                {option.label}
            </button>
        {/each}
    </div>
    {#if error}<p class="mt-2 text-[0.8125rem] text-wg-danger" role="alert">{error}</p>{/if}
{/snippet}
