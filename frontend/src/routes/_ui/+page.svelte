<script lang="ts">
    import Button from "$lib/components/Button.svelte";
    import Dialog from "$lib/components/Dialog.svelte";
    import Field from "$lib/components/Field.svelte";

    let dialogOpen = $state(false);
</script>

<svelte:head>
    <title>WebGuard UI preview</title>
</svelte:head>

<main>
    <header>
        <p>Development-only component preview</p>
        <h1>WebGuard interface primitives</h1>
        <span>Dark-aware, keyboard-accessible and ready for the SvelteKit workspace.</span>
    </header>

    <section aria-labelledby="buttons-heading">
        <h2 id="buttons-heading">Actions</h2>
        <div class="actions">
            <Button>Save changes</Button>
            <Button variant="secondary">Cancel</Button>
            <Button variant="danger">Delete monitoring</Button>
            <Button variant="quiet">View details</Button>
        </div>
    </section>

    <section aria-labelledby="form-heading">
        <h2 id="form-heading">Fields</h2>
        <div class="field-grid">
            <Field label="Monitoring name" required hint="Visible to your team.">
                <input name="name" value="API gateway" />
            </Field>
            <Field label="Target URL" error="Enter a publicly reachable URL.">
                <input name="url" aria-invalid="true" value="localhost" />
            </Field>
        </div>
    </section>

    <section aria-labelledby="dialog-heading">
        <h2 id="dialog-heading">Dialog</h2>
        <Button onclick={() => (dialogOpen = true)}>Open confirmation</Button>
    </section>
</main>

<Dialog bind:open={dialogOpen} title="Discard unsaved changes?" description="Your edits have not been saved yet.">
    <div class="dialog-actions">
        <Button variant="secondary" onclick={() => (dialogOpen = false)}>Keep editing</Button>
        <Button variant="danger" onclick={() => (dialogOpen = false)}>Discard changes</Button>
    </div>
</Dialog>

<style>
    main {
        width: min(70rem, calc(100% - 2rem));
        margin: 0 auto;
        padding: 3rem 0;
    }

    header {
        max-width: 42rem;
        margin-bottom: 2.5rem;
    }

    header p,
    header span {
        color: var(--wg-text-muted);
    }

    header p {
        margin: 0 0 0.5rem;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    h1,
    h2 {
        margin: 0;
    }

    h1 {
        font-size: clamp(2rem, 6vw, 3.5rem);
        line-height: 1.05;
    }

    header span {
        display: block;
        margin-top: 1rem;
        font-size: 1rem;
        line-height: 1.6;
    }

    section {
        margin-top: 1.25rem;
        border: 1px solid var(--wg-border);
        border-radius: 1rem;
        background: var(--wg-surface);
        box-shadow: var(--wg-shadow);
        padding: 1.5rem;
    }

    h2 {
        margin-bottom: 1rem;
        font-size: 1rem;
    }

    .actions,
    .dialog-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    input {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid var(--wg-border);
        border-radius: 0.625rem;
        background: var(--wg-surface);
        color: var(--wg-text);
        padding: 0.65rem 0.75rem;
    }

    input[aria-invalid="true"] {
        border-color: var(--wg-danger);
    }

    @media (max-width: 42rem) {
        main {
            padding: 1.5rem 0;
        }

        section {
            padding: 1.125rem;
        }

        .field-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
