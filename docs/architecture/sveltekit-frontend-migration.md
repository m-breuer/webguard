# SvelteKit frontend migration

**Status:** Accepted for phased implementation.  
**Tracking:** [WebGuard #734](https://github.com/marcel-breuer/webguard/issues/734)  
**Architecture and inventory:** [WebGuard #735](https://github.com/marcel-breuer/webguard/issues/735)

## Context

WebGuard currently renders its browser UI with Laravel Blade, Alpine, Tailwind,
and Vite. The application contains 150 Blade view files, including 47 reusable
components, and a TypeScript entry point that registers Alpine modules for
dashboard loading, monitoring detail and cards, charts, modals, notifications,
and theme handling. The current production image builds those assets with Bun
and serves them from the PHP container.

That arrangement keeps Laravel close to the UI, but it also makes browser state,
modal lifecycle, and progressively added asynchronous views cross-cutting Blade
and Alpine concerns. WebGuard will move the complete browser UI to SvelteKit
while retaining Laravel as the authoritative domain and integration backend.

Laravel 13 offers a Svelte starter through Inertia, but that is a different
topology from the requested SvelteKit application. This decision therefore does
not introduce Inertia as an intermediate UI runtime.

## Decision

WebGuard will run a SvelteKit application with `adapter-node` as a separate
service. A same-origin gateway will expose one public host and route requests to
the correct owner:

```text
browser
   |
same-origin gateway
   |-- browser routes and SvelteKit assets --> SvelteKit SSR service
   |-- Laravel APIs, auth, CSRF, signed links, webhooks, health --> Laravel PHP
   `-- static badge asset --> Laravel PHP
```

Laravel remains responsible for authentication, session storage, CSRF
verification, policy and role enforcement, validation, rate limits, domain
services, jobs, queues, scheduler, persistence, notifications, external APIs,
and scanner-instance APIs. SvelteKit never obtains database credentials and does
not call the database directly.

The gateway makes the browser, SvelteKit, and Laravel appear under one origin.
This avoids a cross-origin session design and keeps current cookie, signed URL,
OAuth callback, and CSRF assumptions valid. It must only trust forwarded headers
from the configured proxy chain.

`adapter-static` with a generic SPA fallback is not the chosen baseline. A
fallback would make dynamic application routes available, but would lose
server-rendered public output and carries documented performance and SEO costs.
The Node adapter gives public status pages and direct links a server-rendered
response while retaining client-side navigation for authenticated workflows.

## Runtime responsibilities

| Service | Owns | Does not own |
| --- | --- | --- |
| Gateway | Canonical host, path dispatch, TLS-facing proxy headers, compression, cache policy, readiness routing | Business authorization, application session data, API serialization |
| SvelteKit | SSR, hydration, frontend route layouts, project-owned components, typed client state, UI loading/error states | Database access, Laravel policy decisions, external API compatibility |
| Laravel PHP | Browser API contracts, sessions, CSRF, policies, validation, domain services, public integrations, health, webhooks | Svelte component rendering after cutover |
| Laravel workers | Queues, scheduler, monitoring execution, notification delivery | Browser requests and SvelteKit rendering |

The production Docker build will use separate, minimal PHP and Node runtime
images plus a gateway image. The local Compose stack will expose SvelteKit HMR
through the canonical local hostname rather than requiring a second browser
origin. Workers and the scheduler remain PHP services.

## Browser-surface inventory

This inventory is derived from `routes/web.php`, `routes/auth.php`, existing
Blade directories, and the JavaScript entry point. Each row is a complete route
family; generated resource actions use the normal Laravel index, create, store,
show, edit, update, and destroy actions unless listed otherwise.

| Audience | Current route family | Current presentation | SvelteKit destination | Tracking |
| --- | --- | --- | --- | --- |
| Anonymous | `/`, `/locale`, `/heartbeat/{token}`, `/api/docs`, `/team-invitations/{token}/accept` | Redirect, small Blade forms, or Laravel response | Preserve Laravel-owned redirects and signed/token endpoints; add SvelteKit confirmation UI where a browser page is rendered | #739 |
| Anonymous public status | `/label/{monitoring}` legacy redirects; `/status/{statusPage}`; legacy status slugs; all subscriber create, confirm, and unsubscribe paths | Public status, status components, subscription forms | SSR public status routes with Laravel-owned public data and subscription actions | #741 |
| Anonymous public integration | `/badge.js` | Standalone JavaScript asset | Keep Laravel-owned route and embed contract; validate gateway cache and headers | #741 |
| Guest authentication | `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, demo and guest credential endpoints | Blade auth views and controller redirects | SvelteKit auth pages backed by Laravel session/auth contracts | #739 |
| Authenticated account | `/verify-email`, `/confirm-password`, `/password`, `/logout`, `/profile` and profile API-key, notification-channel, theme, and deletion actions | Blade forms, modal fragments, redirects | SvelteKit account/settings views and typed mutation responses | #739 |
| Verified member monitoring | `/dashboard`, `/monitorings/*`, monitoring ownership, notification preferences, reset, and server-health-token rotation | Blade, Alpine cards/detail, Chart.js, form modals | SvelteKit dashboard and monitoring workspace | #740 |
| Verified member operations | `/maintenance`, `/monitoring-groups/*`, `/status-pages/*`, incident updates/review/metadata/follow-ups/timeline, `/incidents/analytics` | Blade forms, tables, incident workbench, modals | SvelteKit operational workspace | #740 |
| Verified member collaboration | `/teams/*`, invitations, membership updates, leave action, `/notifications/*` | Blade forms/tables and asynchronous notification controls | SvelteKit collaboration and notification views | #739 |
| Administrator | `/admin`, users, packages, server instances, APIs, and audit logs | Blade dashboard, data tables, form modals | SvelteKit administration section | #742 |

The current view inventory groups are: `components` (47), `admin` (23),
`monitorings` (10), `errors` (10), `mail` (9), `status-pages` (7), `teams` (6),
`profile` (6), `incidents` (6), `monitoring-groups` (5), `layouts` (5), `auth`
(5), `notifications` (4), `maintenance` (2), and single dashboard and Scribe
views. Mail and Scribe views are not browser-application routes and remain
Laravel presentation concerns. Error views remain Laravel fallbacks until the
gateway/SvelteKit error policy is implemented.

Existing UI JSON projections are deliberately narrow: the authenticated,
verified `/api/v1/internal/ui/*` family currently exposes dashboard,
monitoring-list, monitoring-card, and monitoring-detail reads. The new
first-party browser contract expands this boundary without changing the
external v1, mobile, or instance contracts; see [API boundaries](api-boundaries.md).

## Request and security rules

1. Unsafe browser requests use Laravel's session and CSRF protection through the
   same canonical host. SvelteKit forwards only the minimum cookies and headers
   required for the request; it never serializes CSRF tokens or session data into
   public HTML.
2. Every Laravel browser endpoint validates input with Form Requests or an
   equivalent request boundary and authorizes the target resource through
   policies or existing role middleware. A hidden SvelteKit control is never an
   authorization mechanism.
3. SvelteKit server loads call Laravel using an internal service URL where
   possible. Browser mutations go through the canonical host so cookies, rate
   limits, audit context, and CSRF behavior remain consistent.
4. External `/api/v1/*`, mobile, scanner-instance, webhook, subscriber, and
   badge contracts stay on their existing Laravel route families. New UI needs
   receive a versioned first-party contract instead of overloading them.
5. The gateway accepts forwarded origin and address headers only from configured
   trusted proxies. It applies request-size and timeout limits, and strips
   untrusted forwarding headers from direct clients.
6. Public status and badge responses use allowlisted public payloads and
   visibility-aware cache keys. They never include team, token, notification, or
   internal incident metadata.

## Health and route ownership

The current PHP health-check default is `/status`, while the public status page
routes require `/status/{identifier}`. No generic `/status` health route exists.
The infrastructure work in #737 must introduce a Laravel-owned, non-public
readiness endpoint and point PHP, SvelteKit, and gateway health checks to their
respective service endpoints. It must not repurpose or intercept
`/status/{identifier}`.

Gateway path ownership is fixed before cutover:

| Path family | Owner |
| --- | --- |
| `/api/*`, `/sanctum/*`, auth POSTs, signed verification/invitation links, webhooks, `/badge.js`, readiness endpoints | Laravel |
| Public and authenticated browser pages, SvelteKit build assets, frontend error routes | SvelteKit |
| `/status/{identifier}` and subscriber pages | SvelteKit SSR backed by Laravel public endpoints |

The exact gateway configuration and service names are implementation details of
#737, but it must retain canonical URL generation for Laravel and SvelteKit.

## Migration sequence and release gates

1. **Architecture and inventory (#735).** This document is the accepted
   compatibility and topology baseline.
2. **Contracts and runtime (#736, #737).** Deliver the first-party API boundary
   and deployable gateway/SvelteKit topology independently, with no browser
   route cutover.
3. **Shared UI (#738).** Establish components, layout, theme, forms, dialog
   lifecycle, typed request client, localization, and error handling.
4. **Feature migrations (#739–#742).** Migrate one bounded audience/workflow
   area at a time behind reversible route or feature switches. Each area needs
   contract, browser, responsive, accessibility, and rollback evidence.
5. **Quality evidence (#743).** Enforce Svelte checking, production build,
   container smoke tests, browser journeys, performance budgets, accessibility,
   and correlation-safe observability in CI.
6. **Cutover and retirement (#744).** Roll out with a canary, validate all
   browser and public integration paths, rehearse rollback, then remove Blade,
   Alpine, and legacy Vite code only after no supported caller remains.

A rollback restores the prior gateway route switch and the previously deployed
PHP frontend image. Laravel database migrations and public contracts must remain
backwards compatible until the rollback window has expired.

## Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Session or CSRF failures across services | One public origin, explicit cookie/header contract, auth browser tests, and a Laravel-owned CSRF endpoint |
| Gateway breaks direct links or signed URLs | Path ownership table, deep-link smoke tests, proxy-header tests, and canary release |
| Public status pages disclose private data | Separate allowlisted public contracts, resource authorization tests, cache-key tests, and SSR review |
| Dual UI state causes divergent behavior | Feature-area switches, API contracts before page migration, one canonical mutation response, and short compatibility windows |
| Extra Node runtime slows or destabilizes deployment | Minimal runtime image, independent health checks, graceful shutdown, resource budgets, and image smoke tests |
| Regressions in accessibility or modal behavior | Shared component primitives plus keyboard, focus-restoration, and browser regression coverage |

## References

- [Laravel 13 Svelte starter kit](https://laravel.com/framework/docs/13.x/starter-kits)
- [Laravel 13 Vite integration for Svelte](https://laravel.com/framework/docs/13.x/vite)
- [SvelteKit Node adapter](https://svelte.dev/docs/kit/adapter-node)
- [SvelteKit static adapter trade-offs](https://svelte.dev/docs/kit/adapter-static)
- [WebGuard API boundaries](api-boundaries.md)
