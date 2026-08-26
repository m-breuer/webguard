<script lang="ts">
    import { onMount } from "svelte";
    import { appRoutes } from "$lib/routes";
    import AppearanceSelector from "$lib/components/AppearanceSelector.svelte";
    import LocaleSelector from "$lib/components/LocaleSelector.svelte";
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

<div class={`min-h-screen transition-[padding] duration-150 ${collapsed ? "pl-[5.5rem] max-[54rem]:pl-0" : "pl-68 max-[54rem]:pl-0"}`}>
    <aside class={`fixed inset-y-0 left-0 z-20 flex flex-col border-r border-purple-900 bg-purple-950 text-purple-100 transition-[width,transform] duration-150 max-[54rem]:w-[min(18rem,calc(100vw_-_3.5rem))] max-[54rem]:-translate-x-full max-[54rem]:shadow-wg-surface ${collapsed ? "w-[5.5rem]" : "w-68"} ${mobileOpen ? "max-[54rem]:translate-x-0" : ""}`} aria-label="Application navigation">
        <div class="flex min-h-18 items-center justify-between gap-2 px-3.5">
            <a class="flex min-w-0 items-center gap-3 font-extrabold tracking-tight no-underline" href={appRoutes.dashboard} aria-label="WebGuard dashboard">
                <img class="size-9 shrink-0 rounded-[0.65rem] bg-white object-contain p-0.5" src="/brand/webguard-logo.png" alt="" />
                <span class:sr-only={collapsed} class="transition-opacity duration-100 max-[54rem]:not-sr-only">WebGuard</span>
            </a>
            <button class="grid size-9 shrink-0 place-items-center rounded-[0.65rem] border border-purple-700 bg-transparent text-xl text-purple-100 max-[54rem]:hidden" type="button" aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"} title={collapsed ? "Expand sidebar" : "Collapse sidebar"} onclick={toggleCollapsed}>
                <span aria-hidden="true">{collapsed ? "›" : "‹"}</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <a class={`mt-1 flex min-h-11 items-center gap-3 rounded-[0.65rem] border px-3 text-sm font-semibold no-underline ${isActive(appRoutes.dashboard) ? "border-purple-400 bg-purple-800 text-white" : "border-transparent text-purple-200 hover:border-purple-700 hover:bg-purple-900 hover:text-white"}`} href={appRoutes.dashboard}>
                <NavIcon name="home" /> <span>Dashboard</span>
            </a>

            {@render NavSection("Operations", operations)}
            {@render NavSection("Collaboration", collaboration)}
            {#if isAdmin}{@render NavSection("Administration", administration)}{/if}
        </nav>

        <div class="grid gap-3 border-t border-purple-900 p-3.5">
            <div class="grid grid-cols-2 gap-2">
                <AppearanceSelector initialTheme={session.user.theme} />
                <LocaleSelector initialLocale={session.user.locale} />
            </div>
            <a class={`flex min-h-11 items-center gap-3 rounded-[0.65rem] border px-3 text-sm font-semibold no-underline ${isActive(appRoutes.notifications) ? "border-purple-400 bg-purple-800 text-white" : "border-transparent text-purple-200 hover:border-purple-700 hover:bg-purple-900 hover:text-white"}`} href={appRoutes.notifications} aria-label="Notifications" title="Notifications">
                <NavIcon name="bell" /> <span>Notifications</span>
            </a>
            <div class="grid gap-2">
                <a href={appRoutes.profile} class="flex min-h-11 items-center gap-3 px-1 text-sm font-bold no-underline">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-purple-700 text-white">{session.user.name.slice(0, 1).toUpperCase()}</span>
                    <span class:sr-only={collapsed} class="max-[54rem]:not-sr-only">{session.user.name}</span>
                </a>
                <button type="button" class:sr-only={collapsed} class="border-0 bg-transparent text-left text-[0.8125rem] text-purple-200 max-[54rem]:not-sr-only" onclick={() => onSignOut?.()}>Sign out</button>
            </div>
        </div>
    </aside>

    <header class="hidden min-h-16 items-center justify-between border-b border-wg-border bg-wg-surface px-4 max-[54rem]:flex">
        <a class="flex items-center gap-3 font-extrabold tracking-tight no-underline" href={appRoutes.dashboard} aria-label="WebGuard dashboard"><img class="size-9 rounded-[0.65rem] bg-white object-contain p-0.5" src="/brand/webguard-logo.png" alt="" /><span>WebGuard</span></a>
        <button type="button" class="grid size-9 place-items-center rounded-[0.65rem] border border-wg-border bg-transparent text-xl text-wg-text" aria-expanded={mobileOpen} aria-label="Toggle navigation" onclick={() => (mobileOpen = !mobileOpen)}>☰</button>
    </header>

    <main class="min-w-0">{@render children?.()}</main>
</div>

{#snippet NavSection(label: string, items: NavigationItem[])}
    <section class="mt-7" aria-label={label}>
        <h2 class:sr-only={collapsed} class="mb-2 px-3 text-[0.6875rem] tracking-[0.12em] text-purple-300 uppercase max-[54rem]:not-sr-only">{label}</h2>
        {#each items as item}
            <a class={`mt-1 flex min-h-11 items-center gap-3 rounded-[0.65rem] border px-3 text-sm font-semibold no-underline ${isActive(item.href) ? "border-purple-400 bg-purple-800 text-white" : "border-transparent text-purple-200 hover:border-purple-700 hover:bg-purple-900 hover:text-white"}`} href={item.href}>
                <NavIcon name={item.icon} /> <span>{item.label}</span>
            </a>
        {/each}
    </section>
{/snippet}
