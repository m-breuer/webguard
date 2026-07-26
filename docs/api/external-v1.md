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

Scribe generates the OpenAPI and reference documentation from route metadata,
request rules, controller annotations, and this configuration. Do not edit the
generated artifacts manually.
