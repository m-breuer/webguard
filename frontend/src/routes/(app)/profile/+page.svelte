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

<main class="mx-auto w-[min(52rem,calc(100%_-_2rem))] py-6 sm:py-12">
    <header class="mb-8">
        <p class="m-0 text-[0.8125rem] font-extrabold tracking-[0.1em] text-wg-accent uppercase">Account</p>
        <h1 class="mt-2 text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">Profile settings</h1>
        <span class="mt-3 block leading-6 text-wg-text-muted">Update your account details and appearance preference.</span>
    </header>

    <Card title="Account information" description="Changing your email requires verification again.">
        <MutationForm action="/api/v1/internal/ui/profile" method="PATCH" submitLabel="Save changes" successMessage="Profile saved. Refreshing your session…" onSuccess={refreshProfile}>
            <Field label="Name" required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="name" autocomplete="name" value={data.session.user.name} /></Field>
            <Field label="Email" required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="email" type="email" autocomplete="email" value={data.session.user.email} /></Field>
        </MutationForm>
    </Card>

    <Card title="Password" description="Use your current password to confirm this security-sensitive change.">
        <MutationForm action="/api/v1/internal/ui/profile/password" method="PUT" submitLabel="Update password" successMessage="Password updated." >
            <Field label="Current password" required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="current_password" type="password" autocomplete="current-password" /></Field>
            <Field label="New password" required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="password" type="password" autocomplete="new-password" /></Field>
            <Field label="Confirm new password" required><input class="w-full rounded-[0.625rem] border border-wg-border bg-wg-surface px-3 py-[0.65rem] text-wg-text" name="password_confirmation" type="password" autocomplete="new-password" /></Field>
        </MutationForm>
    </Card>
</main>
