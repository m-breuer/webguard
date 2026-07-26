# External API v1 compatibility

`/api/v1/*` is the supported REST API for platform users and integrations. It
uses a Sanctum personal-access token and is deliberately separate from browser
UI routes and scanner-instance routes.

## Compatibility policy

- Existing JSON bodies, status codes, validation errors, and authorization
  behavior are stable v1 contract. Additive fields are allowed; removals and
  shape changes require a new major version.
- The external route file resolves only controllers in
  `App\\Http\\Controllers\\Api\\External`. These are explicit compatibility
  adapters while controller-specific resources are introduced incrementally.
- List endpoints retain their current contract. Monitoring lists accept
  `per_page` from 1 through 100 (default 25); other current list shapes are not
  silently converted to a pagination envelope.

## Rate limits

Non-mobile external tokens have a per-user limit of five requests per 60-second
window. Responses include `X-RateLimit-Limit` and `X-RateLimit-Remaining`; a
limited request returns `429`, `Retry-After`, and zero remaining requests.

The rate limiter deliberately does not log access tokens or authorization
headers. Mobile app tokens retain their existing separate behavior.

## Follow-up

The external API workstream owns explicit resources, error/problem responses,
correlation IDs, idempotency, deprecation policy, and generated Scribe/OpenAPI
coverage. Generated API documents are updated only through their documented
generation process.
