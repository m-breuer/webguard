<script lang="ts">
    import Field from "$lib/components/Field.svelte";
    import GuestAuthLayout from "$lib/components/GuestAuthLayout.svelte";
    import MutationForm from "$lib/components/MutationForm.svelte";

    interface Props { data: { email: string; token: string }; }

    let { data }: Props = $props();
    const fieldClass = "w-full rounded-xl border border-wg-border bg-wg-surface px-3 py-2.5 text-wg-text";
</script>

<svelte:head><title>Choose a new password | WebGuard</title></svelte:head>
<GuestAuthLayout title="Choose a new password" description="Use a unique password that you do not use on any other service.">
    <MutationForm action="/api/v1/internal/ui/auth/reset-password" submitLabel="Reset password" successMessage="Password updated. Redirecting to sign in…" onSuccess={() => window.location.assign("/login?reset=1")}>
        <input name="token" type="hidden" value={data.token} />
        <Field label="Email" required><input class={fieldClass} name="email" type="email" autocomplete="username" value={data.email} required /></Field>
        <Field label="New password" required><input class={fieldClass} name="password" type="password" autocomplete="new-password" required /></Field>
        <Field label="Confirm new password" required><input class={fieldClass} name="password_confirmation" type="password" autocomplete="new-password" required /></Field>
    </MutationForm>
</GuestAuthLayout>
