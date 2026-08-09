# External API v1 compatibility

`/api/v1/*` is the supported REST API for platform users and integrations. It
uses a Sanctum personal-access token and is deliberately separate from browser
UI routes and scanner-instance routes.

## Compatibility policy

- Existing JSON bodies, status codes, validation errors, and authorization
  behavior are stable v1 contract. Additive fields are allowed; removals and
  shape changes require a new major version.
- The external route file resolves only controllers in
  `App\\Http\\Controllers\\Api\\External`. Monitoring data remains an intentional
  adapter to the shared read-model implementation; model-backed responses use
  explicit external resources to preserve their current v1 representation.
- Monitoring lists use the existing Laravel paginator with `per_page` from 1
  through 100 (default 25), ordered by `name` and then `id`. Check history uses
  the existing offset pagination with `limit` from 1 through 1,000 (default 100),
  ordered by descending check timestamp and `id`. Team, membership, invitation,
  and push-device lists retain their legacy complete `data` arrays; their order
  is deterministic (`name`/`id`, creation/`id`, or registration/`id` as
  appropriate). A new paginated envelope for those endpoints requires v2.

## Rate limits

Non-mobile external tokens have a per-user limit of five requests per 60-second
window. Responses include `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and
`X-RateLimit-Reset` (Unix timestamp); a limited request returns `429`,
`Retry-After`, and zero remaining requests.

The rate limiter deliberately does not log access tokens or authorization
headers. Mobile app tokens retain their existing separate behavior.

## Errors, retries, and lifecycle

- Existing v1 validation errors keep Laravel's `{ "message", "errors" }`
  shape. Authorization and missing resources keep their existing HTTP status
  and body. Problem-detail envelopes are reserved for a compatible future API
  version.
- `X-Request-Id` is generated for every authenticated external response,
  including validation and rate-limit responses. Include it when contacting
  support; request tokens and authorization headers are never logged.
- `GET`, `HEAD`, `PUT`, `PATCH`, and `DELETE` follow their HTTP idempotency
  semantics. `POST /mobile-push-devices` is idempotent for a provider/token
  pair. Other creation endpoints do not currently accept `Idempotency-Key`, so
  clients must avoid blind retries after an unknown write outcome.
- API changes are additive within v1. A deprecated v1 endpoint will announce a
  successor through `Deprecation`, `Sunset`, and `Link` headers before removal;
  a response-shape change requires v2.
- Least-privilege token enforcement is available behind
  `EXTERNAL_API_ENFORCE_TOKEN_ABILITIES`. It remains `false` by default for
  backwards compatibility. Once enabled, safe methods require `external:read`
  and mutating methods require `external:write`; existing wildcard tokens
  continue to work.

## Named scoped API keys

Authenticated browser sessions can create, list, inspect, and revoke keys at
`/api/v1/api-keys`. The endpoints use the same authenticated session and CSRF
protection as the profile screen; newly created scoped bearer keys cannot manage
keys themselves. This prevents a telemetry or analytics integration from
creating another key or escalating its permissions.

| Method | Path | Result |
| --- | --- | --- |
| `GET` | `/api/v1/api-keys?state=active|revoked&per_page=25` | Paginated non-secret key metadata. |
| `POST` | `/api/v1/api-keys` | Creates a named key; returns its plaintext value exactly once. |
| `GET` | `/api/v1/api-keys/{id}` | Returns metadata for one key owned by the authenticated user. |
| `DELETE` | `/api/v1/api-keys/{id}` | Immediately and idempotently revokes one owned key. |

Creation requires a non-empty, unique-per-user `name` and a non-empty
`abilities` array. The only new key abilities are:

- `server-health:write`: may only send telemetry to the bearer Server Health
  endpoint for a monitoring the key owner may manage;
- `analytics:read`: may only read the documented monitoring reporting routes
  below; it cannot list or mutate monitorings, teams, devices, or API keys.

Metadata includes the key ID, display name, abilities, non-secret Sanctum
prefix, creation time, last-use time, and revocation state. It never includes a
plaintext token, token hash, or authorization header. Deleted key records are
retained as revoked metadata for auditability and cannot authenticate again.

The analytics routes are `GET /api/v1/monitorings/{monitoring}` and its
`status`, `uptime-downtime`, `uptime-downtime-summary`, `response-times`,
`checks`, `incidents`, `heatmap`, `ssl`, and `uptime-calendar` subresources.
All retain their existing ownership checks and response contracts.

Existing pre-scoped external tokens remain compatible. They are not converted
to new keys automatically; rotate them from the profile when practical.

## Mobile monitoring groups and ownership

Native clients manage personal monitoring groups through `/api/v1/mobile/monitoring-groups`.
The list and assignment-options endpoints use the standard Laravel paginator
(`per_page` 1 through 100, default 25) and order by name and ID. Group payloads
contain private ownership metadata, the number of safely assignable monitorings,
and, for a detail or write response, the assigned private monitoring summaries.

| Method | Path | Result |
| --- | --- | --- |
| `GET` | `/mobile/monitoring-groups` | Paginated groups owned and manageable by the current user. |
| `GET` | `/mobile/monitoring-groups/{id}` | One group and its safely visible private assignments. |
| `GET` | `/mobile/monitoring-groups/assignment-options` | Paginated private monitorings available for assignment. |
| `POST` | `/mobile/monitoring-groups` | Creates a personal group and optional assignments. |
| `PATCH` | `/mobile/monitoring-groups/{id}` | Renames a group and/or replaces its supplied assignments. |
| `DELETE` | `/mobile/monitoring-groups/{id}` | Deletes the group; monitorings remain and their pivot assignments are removed. |

Groups are always personal. Only monitorings privately owned by the current user
can be assigned; team-owned and foreign monitorings are rejected with `422` and
field-keyed Laravel validation errors. A monitoring moved to team ownership is
detached from all personal groups. Monitoring management responses now add
`ownership` and `group_assignments` fields so mobile clients can render the
result of create, update, and ownership changes without browser-form data.

`POST` does not accept an idempotency key and should not be blindly retried after
an unknown outcome. `PATCH` replaces assignments only when `monitoring_ids` is
provided; omitting it leaves existing assignments unchanged. `DELETE` returns
`404` for an unknown or inaccessible group, so clients should re-list groups
after an unknown deletion outcome.

## Mobile status-page incident workspace

Native clients use `/api/v1/mobile/status-pages` for a private workspace that
is deliberately separate from public status-page output and browser management
routes. A status page is visible only to its owner. Incidents are included only
when their monitoring is manageable by that user; team-owned monitorings
therefore require the existing team-administrator permission. Inaccessible
status pages and incidents return `404`, avoiding ownership disclosure.

| Method | Path | Result |
| --- | --- | --- |
| `GET` | `/mobile/status-pages` | Paginated private status-page summaries, including publication state, component and verified subscriber counts, and open incident count. |
| `GET` | `/mobile/status-pages/{statusPage}` | One workspace with safely manageable components and monitoring-group context. |
| `PATCH` | `/mobile/status-pages/{statusPage}/publication` | Publishes or unpublishes the status page with `is_public`. |
| `GET` | `/mobile/status-pages/{statusPage}/incidents?state=open|resolved` | Paginated incident workspaces for the status page. |
| `GET` | `/mobile/status-pages/{statusPage}/incidents/{incident}` | One incident with private metadata, readiness, updates, follow-ups, and timeline. |
| `POST` | `/mobile/status-pages/{statusPage}/incidents/{incident}/updates` | Creates a public incident update. |
| `PATCH` | `/mobile/status-pages/{statusPage}/incidents/{incident}/metadata` | Updates incident severity, affected service, and private metadata. |
| `PATCH` | `/mobile/status-pages/{statusPage}/incidents/{incident}/review` | Updates post-incident review fields. |
| `POST`, `PATCH`, `DELETE` | `/mobile/status-pages/{statusPage}/incidents/{incident}/follow-ups[/{followUp}]` | Creates, updates, or removes follow-up work. |
| `POST`, `PATCH`, `DELETE` | `/mobile/status-pages/{statusPage}/incidents/{incident}/timeline[/{timelineEvent}]` | Creates, updates, or removes custom timeline entries. |

`POST` requests for updates, follow-ups, and custom timeline events require an
`Idempotency-Key` header (one to 100 characters). The key is stored per
incident and communication type: the first request returns `201`, while a
retry with the same key returns the original resource with `200` and creates no
additional update, follow-up, timeline event, or audit record. `PATCH` and
`DELETE` have normal HTTP idempotency semantics; clients should use the returned
current resource state or re-read after an unknown outcome rather than infer a
concurrent write result.

The workspace uses ordinary Laravel field-keyed validation errors. Mutations
are recorded in the activity log with the authenticated user as causer; token
values and authorization headers are never recorded. Public status-page
endpoints and browser management routes keep their existing contracts and do
not expose this private workspace payload.

Scribe generates the OpenAPI and reference documentation from route metadata,
request rules, controller annotations, and this configuration. Do not edit the
generated artifacts manually.
