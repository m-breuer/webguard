<script lang="ts">
    import AuthWorkspace from "$lib/components/AuthWorkspace.svelte";
    import type { AuthOptions } from "$lib/server/auth";

    interface Props { data: { options: AuthOptions; initialEmail: string }; }

    let { data }: Props = $props();
</script>

<svelte:head><title>Create account | WebGuard</title></svelte:head>
<AuthWorkspace options={data.options} initialEmail={data.initialEmail} initialMode="register" />
