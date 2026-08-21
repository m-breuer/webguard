<script lang="ts">
    import Card from "$lib/components/Card.svelte";
    import EmptyState from "$lib/components/EmptyState.svelte";
    import StatusBadge from "$lib/components/StatusBadge.svelte";
    import Button from "$lib/components/Button.svelte";
    import Dialog from "$lib/components/Dialog.svelte";
    import Field from "$lib/components/Field.svelte";
    import MutationForm from "$lib/components/MutationForm.svelte";

    interface TeamSummary { id: string; name: string; description: string | null; member_count: number; monitoring_count: number; role: "admin" | "member"; }
    interface Props { data: { data: { teams: TeamSummary[] } }; }
    let { data }: Props = $props();
    const teams = $derived(data.data.teams);
    let createOpen = $state(false);

    function refreshTeams(): void { window.location.reload(); }
</script>

<svelte:head><title>Teams | WebGuard</title></svelte:head>
<main>
    <header><div><p>Collaboration</p><h1>Teams</h1><span>Manage the monitorings and members you collaborate with.</span></div><Button onclick={() => (createOpen = true)}>Create team</Button></header>
    {#if teams.length === 0}
        <EmptyState title="No teams yet" description="Create a team to share monitoring ownership and collaborate with colleagues." />
    {:else}
        <div class="teams">
            {#each teams as team (team.id)}
                <Card title={team.name} description={team.description ?? "No description provided."}>
                    {#snippet actions()}<StatusBadge tone={team.role === "admin" ? "healthy" : "neutral"} label={team.role === "admin" ? "Admin" : "Member"} />{/snippet}
                    <dl><div><dt>Members</dt><dd>{team.member_count}</dd></div><div><dt>Monitorings</dt><dd>{team.monitoring_count}</dd></div></dl>
                </Card>
            {/each}
        </div>
    {/if}
</main>
<Dialog bind:open={createOpen} title="Create team" description="You will become this team's first administrator.">
    <MutationForm action="/api/v1/internal/ui/teams" submitLabel="Create team" successMessage="Team created. Refreshing…" onSuccess={refreshTeams}>
        <Field label="Name" required><input name="name" autocomplete="organization" /></Field>
        <Field label="Description"><textarea name="description" rows="3"></textarea></Field>
    </MutationForm>
</Dialog>
<style>
    main { width: min(70rem, calc(100% - 2rem)); margin: 0 auto; padding: 3rem 0; } header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:2rem; } h1, p, span { margin: 0; } header p { color: var(--wg-accent); font-size: .8125rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; } h1 { margin-top: .5rem; font-size: clamp(2rem, 6vw, 3rem); line-height: 1.1; } header span { display:block; margin-top:.75rem; color:var(--wg-text-muted); } .teams { display:grid; grid-template-columns:repeat(auto-fit,minmax(17rem,1fr)); gap:1rem; } dl { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin:0; } dt { color:var(--wg-text-muted); font-size:.8125rem; } dd { margin:.25rem 0 0; font-size:1.25rem; font-weight:800; } input,textarea { width:100%; box-sizing:border-box; border:1px solid var(--wg-border); border-radius:.625rem; background:var(--wg-surface); color:var(--wg-text); padding:.65rem .75rem; } @media (max-width:42rem) { header { flex-direction:column; } }
</style>
