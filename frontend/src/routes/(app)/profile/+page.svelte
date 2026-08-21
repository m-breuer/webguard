<script lang="ts">
    import Card from "$lib/components/Card.svelte";
    import Field from "$lib/components/Field.svelte";
    import MutationForm from "$lib/components/MutationForm.svelte";
    import type { FirstPartySession } from "$lib/api/models";

    interface Props {
        data: { session: FirstPartySession };
    }

    let { data }: Props = $props();

    function refreshProfile(): void {
        window.location.reload();
    }
</script>

<svelte:head><title>Profile | WebGuard</title></svelte:head>

<main>
    <header><p>Account</p><h1>Profile settings</h1><span>Update your account details and appearance preference.</span></header>

    <Card title="Account information" description="Changing your email requires verification again.">
        <MutationForm action="/api/v1/internal/ui/profile" method="PATCH" submitLabel="Save changes" successMessage="Profile saved. Refreshing your session…" onSuccess={refreshProfile}>
            <Field label="Name" required><input name="name" autocomplete="name" value={data.session.user.name} /></Field>
            <Field label="Email" required><input name="email" type="email" autocomplete="email" value={data.session.user.email} /></Field>
        </MutationForm>
    </Card>
</main>

<style>
    main { width: min(52rem, calc(100% - 2rem)); margin: 0 auto; padding: 3rem 0; }
    header { margin-bottom: 2rem; } h1, p, span { margin: 0; } header p { color: var(--wg-accent); font-size: 0.8125rem; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; } h1 { margin-top: 0.5rem; font-size: clamp(2rem, 6vw, 3rem); line-height: 1.1; } header span { display: block; margin-top: 0.75rem; color: var(--wg-text-muted); line-height: 1.5; }
    input { width: 100%; box-sizing: border-box; border: 1px solid var(--wg-border); border-radius: 0.625rem; background: var(--wg-surface); color: var(--wg-text); padding: 0.65rem 0.75rem; }
    @media (max-width: 42rem) { main { padding: 1.5rem 0; } }
</style>
