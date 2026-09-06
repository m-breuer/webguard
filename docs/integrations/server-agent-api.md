# Customer server-agent API contract

**Status:** Version 1 implementation contract.  
**Core tracking:** [WebGuard #658](https://github.com/marcel-breuer/webguard/issues/658)  
**Package tracking:** [webguard-server-agent #2](https://github.com/marcel-breuer/webguard-server-agent/issues/2)

This contract is for the customer-installed `webguard-server-agent`. It is not
part of the browser, public API, or scanner-instance API contracts.

## Endpoint and authentication

Each Server Health monitoring creates a private report URL:

```text
POST /api/server-health/{token}
```

The former `/api/v1/server-health/{token}` path remains available as a
compatibility alias for already-installed agents. New installations should use
the canonical path above. The alias has the same authentication, throttling,
validation, and response contract.

The token is the authentication secret. The agent must send HTTPS outside a
trusted local development environment, must not log the URL/token, and must set
`Content-Type: application/json` and `Accept: application/json`. Core permits
60 reports per minute.

The endpoint has no inbound connection to the customer server and exposes no
remote-command mechanism.

During the migration window, an agent may instead use a telemetry-only named
API key created in Profile → API Configuration:

```text
POST /api/server-health/monitorings/{monitoring-id}
Authorization: Bearer {telemetry-only-key}
```

The former `/api/v1/server-health/monitorings/{monitoring-id}` bearer path
also remains available as a compatibility alias during migration.

The key needs the `server-health:write` ability, and its owner must be allowed
to manage the referenced Server Health monitoring. It cannot submit another
user's monitoring, read reports, access browser routes, or call scanner-instance
routes. `401` means the bearer token is absent, invalid, or revoked; `403`
means it lacks the telemetry ability; `404` means the monitoring is not a
manageable Server Health monitoring. Never log the bearer token.

The report URL is displayed only in the authenticated monitoring detail view.
An owner can rotate it there; rotation invalidates the previous token
immediately. The agent should treat a `404` response as a configuration error
and require the new URL instead of retrying.

The private report URL remains supported throughout this migration. Rotating or
revoking a named API key does not change that private URL.

## Version 1 report

```json
{
  "schema_version": 1,
  "report_id": "f1f1c5de-45d1-4d74-b5de-4d0c4df415e7",
  "sampled_at": "2026-08-08T12:00:00Z",
  "host": {
    "cpu_usage_percent": 42.5,
    "logical_cpu_count": 4,
    "load_average_1m": 1.42,
    "load_average_5m": 1.10,
    "load_average_15m": 0.94,
    "ram_usage_percent": 68.2,
    "swap_usage_percent": 4.1,
    "uptime_seconds": 86400
  },
  "service_checks": [
    {
      "id": "app-health",
      "success": true,
      "response_time_ms": 38.4,
      "status_code": 200
    }
  ],
  "agent": { "version": "1.0.0" }
}
```

`report_id` is a UUID and is unique for a monitoring. Replaying the same
report is safe: Core returns `200` with `deduplicated: true` and does not create
another response or trigger further state changes. `sampled_at` must be no more
than five minutes in the future and no older than 24 hours.

The agent may send at most 20 service checks. A check contains only a stable ID
and observed result; its local target, credentials, headers, and request body
must never be sent to Core. Filesystem, mount-path, process, command-line,
environment, and cloud-metadata collection are not part of this contract.

## Evaluation

Core marks a Server Health monitoring down for a configured CPU or RAM breach,
a normalized one-minute load breach, a failed local service check, or a stale
report. A slow successful local service check uses the normal degraded
performance state and does not fabricate an availability outage. The expected
report interval and grace period are configured on the monitoring.

## Compatibility and retries

The existing flat payload remains supported for cron scripts:

```json
{
  "cpu_usage_percent": 42.5,
  "ram_usage_percent": 68.2,
  "load_average": 1.42,
  "uptime_seconds": 86400
}
```

New agents must use the versioned report. Retry only connection failures, timeouts,
429, and 5xx responses with bounded exponential backoff and jitter. Do not retry
401, 404, or 422 responses. A `200` response with `deduplicated: true` is a
successful delivery.
