<script lang="ts">
    import { onMount } from "svelte";
    import { appRoutes } from "$lib/routes";
    import AppearanceSelector from "$lib/components/AppearanceSelector.svelte";
    import NavIcon, { type NavIconName } from "$lib/components/NavIcon.svelte";
    import type { FirstPartySession } from "$lib/api/models";
    import type { Snippet } from "svelte";

    interface NavigationItem {
        href: string;
        icon: NavIconName;
        label: string;
    }

    interface Props {
        session: FirstPartySession;
        currentPath: string;
        children?: Snippet;
        onSignOut?: () => void | Promise<void>;
    }

    const operations: NavigationItem[] = [
        { href: appRoutes.monitorings, icon: "monitoring", label: "Monitorings" },
        { href: appRoutes.monitoringGroups, icon: "group", label: "Monitoring groups" },
        { href: appRoutes.statusPages, icon: "file", label: "Status pages" },
        { href: appRoutes.incidents, icon: "chart", label: "Incidents" },
        { href: appRoutes.maintenance, icon: "wrench", label: "Maintenance" },
    ];
    const collaboration: NavigationItem[] = [{ href: appRoutes.teams, icon: "team", label: "Teams" }];
    const administration: NavigationItem[] = [
        { href: appRoutes.adminDashboard, icon: "dashboard", label: "Dashboard" },
        { href: appRoutes.adminUsers, icon: "users", label: "Users" },
        { href: appRoutes.adminPackages, icon: "package", label: "Packages" },
        { href: appRoutes.adminInstances, icon: "archive", label: "Server instances" },
        { href: appRoutes.adminApi, icon: "api", label: "API access" },
        { href: appRoutes.adminActivityLogs, icon: "activity", label: "Activity logs" },
    ];

    let { session, currentPath, children, onSignOut }: Props = $props();
    let collapsed = $state(false);
    let mobileOpen = $state(false);

    const collapseStorageKey = "webguard.sidebar.collapsed";
    const isAdmin = $derived(session.user.role === "admin");

    onMount(() => {
        collapsed = localStorage.getItem(collapseStorageKey) === "true";
    });

    function toggleCollapsed(): void {
        collapsed = !collapsed;
        localStorage.setItem(collapseStorageKey, String(collapsed));
    }

    function isActive(href: string): boolean {
        return currentPath === href || (href !== appRoutes.dashboard && currentPath.startsWith(`${href}/`));
    }
</script>

<div class:collapsed class="app-shell">
    <aside class:mobile-open={mobileOpen} aria-label="Application navigation">
        <div class="brand-row">
            <a class="brand" href={appRoutes.dashboard} aria-label="WebGuard dashboard">
                <span class="brand-mark">W</span>
                <span class="brand-name">WebGuard</span>
            </a>
            <button class="collapse" type="button" aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"} title={collapsed ? "Expand sidebar" : "Collapse sidebar"} onclick={toggleCollapsed}>
                <span aria-hidden="true">{collapsed ? "›" : "‹"}</span>
            </button>
        </div>

        <nav>
            <a class:active={isActive(appRoutes.dashboard)} class="destination dashboard" href={appRoutes.dashboard}>
                <NavIcon name="home" /> <span>Dashboard</span>
            </a>

            {@render NavSection("Operations", operations)}
            {@render NavSection("Collaboration", collaboration)}
            {#if isAdmin}{@render NavSection("Administration", administration)}{/if}
        </nav>

        <div class="utilities">
            <AppearanceSelector initialTheme={session.user.theme} compact={collapsed} />
            <a class:active={isActive(appRoutes.notifications)} class="utility-link" href={appRoutes.notifications} aria-label="Notifications" title="Notifications">
                <NavIcon name="bell" /> <span>Notifications</span>
            </a>
            <div class="profile">
                <a href={appRoutes.profile} class="profile-link">
                    <span class="avatar">{session.user.name.slice(0, 1).toUpperCase()}</span>
                    <span class="profile-name">{session.user.name}</span>
                </a>
                <button type="button" class="sign-out" onclick={() => onSignOut?.()}>Sign out</button>
            </div>
        </div>
    </aside>

    <header class="mobile-bar">
        <a class="brand" href={appRoutes.dashboard} aria-label="WebGuard dashboard"><span class="brand-mark">W</span><span>WebGuard</span></a>
        <button type="button" class="menu-button" aria-expanded={mobileOpen} aria-label="Toggle navigation" onclick={() => (mobileOpen = !mobileOpen)}>☰</button>
    </header>

    <main>{@render children?.()}</main>
</div>

{#snippet NavSection(label: string, items: NavigationItem[])}
    <section class="nav-section" aria-label={label}>
        <h2>{label}</h2>
        {#each items as item}
            <a class:active={isActive(item.href)} class="destination" href={item.href}>
                <NavIcon name={item.icon} /> <span>{item.label}</span>
            </a>
        {/each}
    </section>
{/snippet}

<style>
    .app-shell { min-height: 100vh; padding-left: 17rem; }
    aside { position: fixed; inset: 0 auto 0 0; z-index: 20; display: flex; width: 17rem; flex-direction: column; border-right: 1px solid #581c87; background: #3b0764; color: #f3e8ff; transition: width 150ms ease, transform 150ms ease; }
    .collapsed { padding-left: 5.5rem; }
    .collapsed aside { width: 5.5rem; }
    .brand-row { display: flex; min-height: 4.5rem; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0 0.875rem; }
    .brand, .destination, .utility-link, .profile-link { display: flex; align-items: center; gap: 0.75rem; color: inherit; text-decoration: none; }
    .brand { min-width: 0; font-weight: 800; letter-spacing: -0.025em; }
    .brand-mark { display: grid; width: 2.25rem; height: 2.25rem; flex: none; place-items: center; border-radius: 0.65rem; background: white; color: #6b21a8; font-weight: 900; }
    .brand-name, .destination span, .utility-link span, .profile-name, .sign-out, h2 { transition: opacity 100ms ease; }
    .collapse, .menu-button { display: inline-grid; width: 2.25rem; height: 2.25rem; flex: none; place-items: center; border: 1px solid #7e22ce; border-radius: 0.65rem; background: transparent; color: #f3e8ff; font-size: 1.25rem; }
    nav { flex: 1; overflow-y: auto; padding: 1rem 0.75rem; }
    .destination, .utility-link { min-height: 2.75rem; margin-top: 0.25rem; border: 1px solid transparent; border-radius: 0.65rem; padding: 0 0.75rem; color: #e9d5ff; font-size: 0.875rem; font-weight: 650; }
    .destination:hover, .utility-link:hover { border-color: #7e22ce; background: #581c87; color: white; }
    .destination.active, .utility-link.active { border-color: #c084fc; background: #6b21a8; color: white; }
    .nav-section { margin-top: 1.75rem; }
    h2 { margin: 0 0 0.5rem; padding: 0 0.75rem; color: #d8b4fe; font-size: 0.6875rem; letter-spacing: 0.12em; text-transform: uppercase; }
    .utilities { display: grid; gap: 0.75rem; border-top: 1px solid #581c87; padding: 0.875rem; }
    .profile { display: grid; gap: 0.5rem; }
    .profile-link { min-height: 2.75rem; padding: 0 0.25rem; font-size: 0.875rem; font-weight: 700; }
    .avatar { display: grid; width: 2rem; height: 2rem; flex: none; place-items: center; border-radius: 50%; background: #7e22ce; color: white; }
    .sign-out { border: 0; background: transparent; color: #e9d5ff; font: inherit; font-size: 0.8125rem; text-align: left; }
    .mobile-bar { display: none; }
    main { min-width: 0; }
    .collapsed .brand-name, .collapsed .destination span, .collapsed .utility-link span, .collapsed .profile-name, .collapsed .sign-out, .collapsed h2 { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
    .collapsed .brand-row { justify-content: center; } .collapsed .collapse { position: absolute; top: 3.75rem; right: -1rem; background: #581c87; } .collapsed .destination, .collapsed .utility-link, .collapsed .profile-link { justify-content: center; padding: 0; }
    @media (max-width: 54rem) { .app-shell, .collapsed { padding-left: 0; } aside, .collapsed aside { width: min(18rem, calc(100vw - 3.5rem)); transform: translateX(-100%); box-shadow: var(--wg-shadow); } aside.mobile-open { transform: translateX(0); } .mobile-bar { display: flex; min-height: 4rem; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--wg-border); background: var(--wg-surface); padding: 0 1rem; } .menu-button { border-color: var(--wg-border); color: var(--wg-text); } .collapse { display: none; } .collapsed .brand-name, .collapsed .destination span, .collapsed .utility-link span, .collapsed .profile-name, .collapsed .sign-out, .collapsed h2 { position: static; width: auto; height: auto; overflow: visible; clip: auto; white-space: normal; } .collapsed .destination, .collapsed .utility-link, .collapsed .profile-link { justify-content: flex-start; padding: 0 0.75rem; } }
</style>
