# UX Baseline: Operations-First WebGuard UI

This document records the repository-grounded baseline for issue [#442](https://github.com/marcel-breuer/webguard/issues/442), which is the research prerequisite for the UI epic [#441](https://github.com/marcel-breuer/webguard/issues/441). The measurements capture the pre-change state used to choose the first #441 implementation slice; subsequent implementation PRs may reduce the friction described here.

## Scope and method

The baseline was completed on 2026-07-17 as a scripted internal walkthrough of the authenticated WebGuard surface. The current implementation is SvelteKit; historical Blade implementation details were retired during the #744 cutover. The desktop reference is a viewport at least 640px wide; the mobile reference is the `<640px` responsive branch, represented by a 390px-wide viewport.

This is not user telemetry and does not claim observed behaviour from external participants. Route transitions, visible action counts, and additional disclosure steps below are repeatable source-based measurements. Real-user sessions should be added before committing to a larger visual redesign.

## Task map and baseline measurements

The counts below start on the authenticated dashboard where a dashboard path exists. A route transition means a new page load; an expand action means opening an existing disclosure control. Login and form submission are excluded.

| Task | Desktop journey and baseline | Mobile journey and baseline | Current evidence |
| --- | --- | --- | --- |
| Find the current status of a monitored service | `/dashboard` → the relevant attention item → `/monitorings/{monitoring}`: 1 transition when the item is visible; otherwise `/dashboard` → `/monitorings` → detail: 2 transitions. | The dashboard attention link is already direct. From another page, open the menu, then choose Monitoring: 1 menu action plus 1 route transition. | SvelteKit dashboard and shared application layout. |
| Identify what needs attention after a failure | `/dashboard` exposes the recommended action, attention list, health counters, and recent incidents in the first operations view. The next action is normally 1 transition. | The same dashboard links remain available in the single-column layout; the global menu is not required when starting on `/dashboard`. | Covered by `tests/Feature/DashboardOverviewTest.php`. |
| Open and update an incident | `/dashboard` → `/incidents/analytics` is 1 transition for analysis. Public incident context is managed from `/status-pages/{statusPage}`. | From a non-dashboard page, add the menu open action before entering Status Pages. | SvelteKit incident analytics and status-page workspace routes. |
| Schedule maintenance for one monitor or group | `/dashboard` → `/maintenance`: 1 transition, then submit the central maintenance form in the SvelteKit workspace. | From another page, open the menu and choose Maintenance. | SvelteKit maintenance route and first-party UI API. |
| Create a new HTTP monitoring | `/dashboard` → `/monitorings`, then open the create dialog. | The modal remains responsive and keyboard accessible. | SvelteKit monitoring list and shared dialog/form components. |
| Find and share a public status page | `/dashboard` → `/status-pages` → `/status-pages/{statusPage}`: 2 transitions; the public URL is shown on the detail page only when the page is public. | From another page, open the menu before Status Pages, then use the same two-route journey. | SvelteKit status-page routes and public status payload API. |

### View-level measurements

- The desktop primary navigation exposes Monitoring, Incidents, and Status Pages directly. Maintenance, monitoring groups, and teams are behind the **More** dropdown; Dashboard is not a primary navigation item, and the logo links to `/monitorings`.
- The mobile navigation is closed by default. Operations, collaboration, and administration destinations are only rendered in the expanded menu; the notification bell and language switch remain outside it.
- The monitoring index offers four preset filters before the advanced filter disclosure, followed by search and type, lifecycle, group, team, ownership, health, maintenance, and sort controls.
- The monitoring form keeps type-dependent controls in one long form. Advanced request settings are disclosed separately, while sharing, notification, and operational settings remain further down the page.
- The status-page detail view displays the public URL near the page title, but incident work is placed inside a per-incident `<details>` disclosure after the public incident updates.

## Findings: repository evidence

1. **The default authenticated entry point bypasses the operations overview.** Registration, email verification, and password-related entry points redirect to `monitorings.index`, while `/dashboard` already contains health, attention, maintenance, incident, trend, and quick-action blocks. This makes the current “first screen” different from the documented operations-first intent.
2. **The dashboard is hard to rediscover.** It is not linked from the primary navigation, and the application logo points to the monitoring list. A user who leaves the dashboard has no visible Dashboard destination in the shared navigation.
3. **Incident work is split across two mental models.** Incident analytics is a direct navigation destination, while incident updates, metadata, follow-ups, and timeline actions live on a status-page detail screen behind a collapsed workbench. The repository has tests for both behaviours, but the navigation does not explain the relationship.
4. **The monitoring list has a powerful but dense filter surface.** Presets help with common states, but the advanced disclosure contains many independent dimensions. This is useful for experienced operators and likely costly for first-time task completion, especially on mobile.
5. **Monitoring creation is complete but vertically expensive.** The form supports many monitoring types and operational controls in one route. The shared form test verifies consistent section order and action placement, but the baseline does not yet show which fields users expect to configure first.

## Findings: hypotheses to validate with participants

- Users will look for Dashboard or Overview in the primary navigation before opening Monitoring.
- “Incident” and “Status page incident update” may be understood as one workflow rather than two destinations.
- First-time users will prefer a small set of status presets over opening advanced filters.
- HTTP monitoring creation can likely be shortened by showing only the fields required for the selected type and moving the rest behind progressive disclosure.
- On mobile, a persistent or contextual next action may outperform a menu-first workflow for failure response and maintenance scheduling.

These are hypotheses, not observations. They must be tested with participants rather than treated as design requirements.

## Terminology recommendations

| Concept | English recommendation | German recommendation | Current observation |
| --- | --- | --- | --- |
| Monitoring resource | Monitoring | Monitoring | The German UI already uses both “Monitoring” and “Überwachung”; the mix is visible across form and navigation copy. |
| Service disruption record | Incident | Incident | “Incident” is already used in the dashboard, analytics, and status-page workbench. |
| Customer-facing surface | Status page | Statusseite | The current German navigation and status-page screens use “Statusseite”. |
| Planned suppression window | Maintenance | Wartung | The current dashboard, navigation, and maintenance screens use “Maintenance” / “Wartung”. |
| Main daily entry | Overview | Übersicht | The current `/dashboard` is an overview in behaviour, but its label is not exposed in the main navigation. |

Recommended terminology should be confirmed during participant sessions, with one term per concept across navigation, headings, filters, and primary actions.

## Recommendation for #441

Start with work package 2, Navigation and Information Architecture, using the already implemented dashboard as the baseline rather than designing a second overview. The first validation candidate should make the dashboard discoverable and compare two entry flows: dashboard-first versus monitoring-list-first. The next candidate should test a direct path from an attention item to the relevant incident workbench, while preserving the existing public/internal separation.

The follow-up study should recruit representative operators, evaluate at least five tasks on desktop and three on mobile, record success, wrong turns, time to first meaningful action, and terminology, and attach annotated screenshots or session links to #442 before the navigation or detail-page redesign is finalized.

## Repository references

- Dashboard: [`frontend/src/routes/(app)/dashboard`](../frontend/src/routes/(app)/dashboard)
- Shared navigation: [`frontend/src/lib/components/AppSidebar.svelte`](../frontend/src/lib/components/AppSidebar.svelte)
- Monitoring list and filters: [`frontend/src/routes/(app)/monitorings`](../frontend/src/routes/(app)/monitorings)
- Status pages: [`frontend/src/routes/(app)/status-pages`](../frontend/src/routes/(app)/status-pages)
