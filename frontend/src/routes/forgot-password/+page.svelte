<script lang="ts">
    import Field from "$lib/components/Field.svelte";
    import GuestAuthLayout from "$lib/components/GuestAuthLayout.svelte";
    import MutationForm from "$lib/components/MutationForm.svelte";

    const fieldClass = "w-full rounded-xl border border-wg-border bg-wg-surface px-3 py-2.5 text-wg-text";
</script>

<svelte:head><title>Reset password | WebGuard</title></svelte:head>
<GuestAuthLayout title="Reset your password" description="Enter your email address and we will send you a password reset link.">
    <MutationForm action="/api/v1/internal/ui/auth/forgot-password" submitLabel="Email reset link" successMessage="If the account exists, a reset link has been sent.">
        <Field label="Email" required><input class={fieldClass} name="email" type="email" autocomplete="email" required /></Field>
    </MutationForm>
    <a class="mt-6 inline-flex text-sm font-bold text-wg-accent no-underline hover:underline" href="/login">Back to sign in</a>
</GuestAuthLayout>
