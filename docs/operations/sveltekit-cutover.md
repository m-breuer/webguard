# SvelteKit production cutover

**Tracking:** [WebGuard #744](https://github.com/marcel-breuer/webguard/issues/744)

This runbook governs the production cutover of the browser UI. The gateway
already makes SvelteKit the primary owner of browser `GET` and `HEAD` requests.
Laravel remains the authority for application data and the owner of the
explicit paths below. Do not remove the Laravel fallback or Blade files as part
of a deployment; retirement is a separate, evidence-based change after the
rollback window.

## Route ownership

| Request family | Owner | Cutover requirement |
| --- | --- | --- |
| Authenticated and guest browser pages | SvelteKit | Verify the complete route matrix at desktop and mobile widths. |
| Canonical public status pages and their confirmation/unsubscribe pages | SvelteKit | Verify SSR, subscription, confirmation, unsubscribe, cache headers, and the canonical `/status/{id}` URL. |
| `/api`, `/api/*`, `/sanctum/*` | Laravel | Keep the same origin, cookies, CSRF endpoint, policy checks, and response contracts. |
| `/status` health endpoint and `/badge.js` | Laravel | Confirm health probes and existing embeds before every promotion. |
| `/heartbeat/{token}`, `/locale`, signed invitation and legacy public URLs | Laravel fallback | Exercise valid and expired tokens; do not turn a SvelteKit `404` into a gateway error. |
| Unsupported browser URLs | Laravel fallback | Keep the gateway's `error_page 404 = @laravel` policy through the rollback window. |

Laravel mail templates, Scribe output, framework error pages, and token/signed
link fallbacks are not browser-layout retirement candidates. Retire only a
Blade view once its callers, fallback behaviour, and tests have been removed
in a dedicated pull request.

## Release prerequisites

- The candidate commit has a green CI run, including the SvelteKit topology
  smoke test.
- The deployed `php`, `frontend`, `gateway`, `schedule`, and `queue-default`
  images all use the same release artifact.
- Gateway, Laravel, and SvelteKit health endpoints return `2xx`:
  `/_health/gateway`, `/_health/laravel`, and `/_health/frontend`.
- The release owner records the commit SHA, deployed image digests, target
  domain, locale, and release window.
- A prior production artifact is available for rollback. Database migrations
  and public API contracts must remain backwards-compatible for the whole
  rollback window.

## Staging and canary checklist

Run the checks through the public gateway, never against a container port:

1. Use a verified member account to sign in, create or edit a monitoring,
   change profile settings, create a team, and mark a notification as read.
   Confirm each mutation produces one write and displays the returned state.
2. At both `1280px` and `390px`, check the dashboard, monitoring list/detail,
   create/edit form, maintenance, status-page management, teams, notifications,
   profile, and every administrator workspace.
3. Verify guest registration, password reset, confirmation, email
   verification, logout, expired-session recovery, and the valid and expired
   team-invitation links.
4. Verify a public status page, subscription confirmation, unsubscribe link,
   legacy `/label` redirect, and an existing `/badge.js` embed.
5. Check queue and scheduler health after an operational mutation. Confirm
   scanner-instance, webhook, and external API consumers remain on Laravel
   contract paths.
6. Start with a platform-managed canary that preserves the current gateway
   configuration. Promote only when request correlation logs show the expected
   upstream owner and no unexpected Laravel fallback for supported SvelteKit
   routes.

The release evidence must include the browser route, viewport, locale, theme,
commit SHA, reviewer, measured navigation time, and screenshot or trace. Apply
the budgets in [SvelteKit quality gates](../frontend/sveltekit-quality-gates.md).

## Promotion and monitoring

Observe the canary for a full representative monitoring cycle, then promote
only when all of the following hold:

- Gateway, Laravel, and SvelteKit readiness remain healthy.
- Browser error rate, `5xx` rate, and authentication/CSRF failures do not
  exceed the established production baseline.
- Public status SSR is within the documented `1,000 ms` budget and authenticated
  navigation is within the documented `1,500 ms` budget.
- Queue depth, scheduler execution, notification delivery, webhook failures,
  and scanner-instance responses remain within their normal operating range.
- No user-critical workflow has an unexplained Laravel-fallback request ID.

Keep enhanced request-ID log correlation and user-workflow monitoring enabled
for at least one representative monitoring cycle after full promotion.

## Rollback

If a cutover criterion fails, stop promotion and redeploy the previously
recorded release artifact through the deployment platform. Restore the prior
gateway/frontend pair together; do not roll back only one of the two services.
Keep Laravel workers and the database at the compatible release unless the
incident procedure explicitly requires a database rollback.

After rollback:

1. Confirm all three health endpoints and the queue/scheduler probes.
2. Repeat sign-in, public status, subscription, badge, and signed-link checks.
3. Attach request IDs, gateway logs, browser traces, and the deployment artifact
   identifiers to the incident or release record.
4. Do not retry promotion until the failure has a tracked fix and a new staging
   rehearsal.

## Blade and Alpine retirement gate

Open a dedicated retirement pull request only after all of the following are
recorded for the completed rollback window:

- no supported browser path requires a Laravel-rendered page;
- staging and production evidence covers the route matrix above;
- fallback logs show no unsupported dependency on legacy browser views;
- mail, Scribe, framework errors, and signed/token flows have explicitly
  retained renderers or tested replacements; and
- dependency and asset removal passes the full CI and release rehearsal.

This gate prevents an operational rollout from silently deleting the recovery
surface needed for public integrations or signed links.
