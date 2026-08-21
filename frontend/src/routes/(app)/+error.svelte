<script lang="ts">
    import { page } from "$app/state";
    import { appRoutes } from "$lib/routes";
</script>

<svelte:head>
    <title>Unable to load | WebGuard</title>
</svelte:head>

<main>
    <p class="eyebrow">{page.status}</p>
    <h1>Unable to load this page</h1>
    <p>{page.error?.message ?? "Please try again in a moment."}</p>
    <a href={appRoutes.dashboard}>Return to dashboard</a>
</main>

<style>
    main { display: grid; min-height: 100vh; max-width: 38rem; align-content: center; gap: 1rem; margin: 0 auto; padding: 2rem; }
    h1, p { margin: 0; }
    h1 { font-size: clamp(2rem, 8vw, 3.5rem); line-height: 1.05; }
    p { color: var(--wg-text-muted); line-height: 1.6; }
    .eyebrow { color: var(--wg-accent); font-size: 0.8125rem; font-weight: 800; letter-spacing: 0.12em; }
    a { width: fit-content; border-radius: 0.75rem; background: var(--wg-accent); color: var(--wg-accent-contrast); padding: 0.75rem 1rem; font-weight: 700; text-decoration: none; }
</style>
