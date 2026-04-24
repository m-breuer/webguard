# WebGuard Instance Prompt: Domain Expiration Monitoring

Implement domain expiration monitoring in the Go-based `webguard-instance` repository so it can process monitorings with type `domain_expiration` from the WebGuard core API.

## Context

The WebGuard core now supports a monitoring type named `domain_expiration`.

Instances receive monitorings from:

```http
GET /api/v1/internal/monitorings?location=<instance-code>
GET /api/v1/internal/monitorings?location=<instance-code>&type=domain_expiration
```

Each monitoring payload includes at least:

```json
{
  "id": "01...",
  "name": "Domain Expiry",
  "type": "domain_expiration",
  "target": "example.com",
  "status": "active",
  "preferred_location": "de-1",
  "maintenance_active": false,
  "domain_expires_at": null,
  "domain_registrar": null,
  "latest_http_status_code": null
}
```

The target is a bare domain name, not a URL. Examples: `example.com`, `subdomain.example.com`. Do not require or prepend `https://`.

## Required Behavior

1. Include `domain_expiration` monitorings in the instance scheduling/check loop.
2. Skip checks when `maintenance_active` is true, consistent with existing monitoring types.
3. Resolve the domain registration expiration date using WHOIS/RDAP where possible.
4. Mark the check:
   - `up` when the domain exists, an expiration date is found, and the expiration is more than 30 days away.
   - `down` when the domain is expired, unavailable, cannot be resolved to a registration record, or expires within 30 days.
   - `unknown` only for temporary lookup failures where retrying later is appropriate.
5. Post the normal monitoring response after each check:

```http
POST /api/v1/internal/monitoring-responses
Content-Type: application/json
X-INSTANCE-CODE: <instance-code>
X-API-KEY: <instance-api-key>

{
  "monitoring_id": "<monitoring-id>",
  "status": "up|down|unknown",
  "http_status_code": null,
  "response_time": null
}
```

6. Post the domain expiration metadata when a lookup result is available:

```http
POST /api/v1/internal/domain-results
Content-Type: application/json
X-INSTANCE-CODE: <instance-code>
X-API-KEY: <instance-api-key>

{
  "monitoring_id": "<monitoring-id>",
  "is_valid": true,
  "expires_at": "2026-07-23T12:00:00Z",
  "registrar": "Example Registrar",
  "checked_at": "2026-04-24T12:00:00Z"
}
```

Use `is_valid: false` when the domain is expired, expiring within 30 days, unavailable, or no expiration date can be found. `registrar` may be `null` if unavailable. `expires_at` may be `null` if no reliable expiration date can be extracted.

## Implementation Notes

- Prefer RDAP for TLDs where it is reliable, and fall back to WHOIS parsing when needed.
- Normalize targets to lowercase and trim trailing dots before lookup.
- Handle common WHOIS expiration fields, including `Registry Expiry Date`, `Registrar Registration Expiration Date`, `Expiration Date`, `paid-till`, and `Expiry Date`.
- Use timeouts for all network calls.
- Avoid panics on malformed WHOIS/RDAP responses.
- Add unit tests for parsing multiple date formats.
- Add integration-style tests for the scheduler/check dispatcher using mocked lookup results and mocked WebGuard core API calls.
- Keep the API client consistent with the existing internal response posting code.

## Acceptance Criteria

- `domain_expiration` monitorings are fetched and checked by the instance.
- The instance posts one status response per completed domain expiration check.
- The instance posts domain metadata to `/api/v1/internal/domain-results`.
- Expiring-within-30-days domains produce a `down` status.
- Valid domains expiring later than 30 days produce an `up` status.
- Lookup failures are handled without crashing the worker.
