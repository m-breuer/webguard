<script lang="ts">
    import AuthWorkspace from "$lib/components/AuthWorkspace.svelte";
    import type { AuthOptions } from "$lib/server/auth";

    interface Props {
        data: {
            options: AuthOptions;
            initialEmail: string;
            initialMode: "login" | "register" | "demo";
            expired: boolean;
            notice: string;
        };
    }

    let { data }: Props = $props();
</script>

<svelte:head><title>Sign in | WebGuard</title></svelte:head>
<AuthWorkspace options={data.options} initialEmail={data.initialEmail} initialMode={data.initialMode} expired={data.expired} notice={data.notice} />
