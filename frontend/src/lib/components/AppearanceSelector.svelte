<script lang="ts">
    import { onMount } from "svelte";
    import { applyAppearanceTheme, storedAppearanceTheme } from "$lib/appearance";
    import { FirstPartyApiError, requestFirstPartyApi } from "$lib/api/client";
    import type { AppearanceTheme } from "$lib/api/models";

    interface Props {
        initialTheme?: AppearanceTheme;
        endpoint?: string;
    }

    let { initialTheme, endpoint = "/api/v1/internal/ui/appearance" }: Props = $props();
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

<fieldset aria-busy={saving}>
    <legend>Appearance</legend>
    <div class="options">
        {#each options as option}
            <button
                type="button"
                aria-pressed={theme === option.value}
                disabled={saving}
                onclick={() => select(option.value)}
            >
                {option.label}
            </button>
        {/each}
    </div>
    {#if error}<p role="alert">{error}</p>{/if}
</fieldset>

<style>
    fieldset {
        margin: 0;
        padding: 0;
        border: 0;
    }

    legend {
        margin-bottom: 0.5rem;
        color: var(--wg-text);
        font-size: 0.875rem;
        font-weight: 700;
    }

    .options {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        overflow: hidden;
        border: 1px solid var(--wg-border);
        border-radius: 0.75rem;
    }

    button {
        min-height: 2.5rem;
        border: 0;
        border-right: 1px solid var(--wg-border);
        background: var(--wg-surface);
        color: var(--wg-text-muted);
        font-size: 0.8125rem;
        font-weight: 700;
    }

    button:last-child {
        border-right: 0;
    }

    button[aria-pressed="true"] {
        background: var(--wg-accent);
        color: var(--wg-accent-contrast);
    }

    button:disabled {
        cursor: wait;
        opacity: 0.65;
    }

    p {
        margin: 0.5rem 0 0;
        color: var(--wg-danger);
        font-size: 0.8125rem;
    }
</style>
