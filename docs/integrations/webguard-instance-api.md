# WebGuard Instance API contract

**Status:** Current compatibility baseline and planned path migration.  
**Core tracking:** [WebGuard Core #593](https://github.com/marcel-breuer/webguard/issues/593)  
**Instance tracking:** [webguard-instance #35](https://github.com/marcel-breuer/webguard-instance/issues/35)

This document is the implementation contract for the separate
[`webguard-instance`](https://github.com/marcel-breuer/webguard-instance)
repository. It is the only Core document that describes scanner-instance API
changes. Browser UI routes are not part of this contract.

## Compatibility and base URL

Core exposes both `/api/v1/internal` and `/api/v1/internal/instances` with
equivalent behavior. The legacy path remains available until both linked issues
have completed their contract-test and rollout conditions.

The instance adapter must make the Core base URL configurable and must support
both bases during that compatibility window:

```text
Legacy:  https://core.example/api/v1/internal
Target:  https://core.example/api/v1/internal/instances
```

For local Docker setup, see [installation](../installation.md#webguard-instance-integration-with-local-docker).

## Authentication and transport

Every scanner request sends these headers:

| Header | Required | Description |
| --- | --- | --- |
| `X-INSTANCE-CODE` | yes | Active `server_instances.code` identifying the scanner location. |
| `X-API-KEY` | yes | Instance secret verified by Core; never log, persist in output, or include in retry diagnostics. |
| `Accept: application/json` | recommended | Requests JSON responses. |
| `Content-Type: application/json` | required for writes | Encodes callback payloads. |

Core rejects missing, inactive, unknown, or invalid credentials with `401` and
`{"message":"Unauthorized"}`. A scanner may access only monitorings assigned to
its authenticated instance code. It must use HTTPS outside trusted local Docker
networks.

## Current endpoints and target paths

The paths below are equivalent compatibility routes. Core will not remove or
redirect the legacy paths before the coordinated rollout.

| Method | Current path | Target path | Purpose |
| --- | --- | --- | --- |
| `GET` | `/monitorings` | `/monitorings` | Fetch active monitorings assigned to the instance. |
| `POST` | `/monitoring-responses` | `/monitoring-responses` | Submit a monitoring result. |
| `POST` | `/incidents` | `/incidents` | Open an incident for a single-location monitoring. |
| `PUT` | `/incidents/{monitoring}` | `/incidents/{monitoring}` | Close an open incident. |
| `POST` | `/ssl-results` | `/ssl-results` | Submit SSL result data. |
| `POST` | `/domain-results` | `/domain-results` | Submit domain-expiration result data. |

For the current column, prefix each path with the legacy base URL. For the target
column, prefix it with the target base URL; the suffix is intentionally the same.

## Monitoring retrieval

`GET /monitorings` requires the following query parameter:

| Parameter | Required | Rules |
| --- | --- | --- |
| `location` | yes | Active instance code; must equal `X-INSTANCE-CODE`. |
| `type` | no | One supported monitoring type. Without it, Core excludes heartbeat and server-health monitorings. |

The successful response is a JSON array. Each monitoring item contains its stable
`id`, `name`, `type`, `target`, network and HTTP settings, expected statuses,
maintenance state, preferred locations, heartbeat settings, and type-specific
domain or server-health fields. The exact current projection is implemented by
`App\\Http\\Resources\\Instance\\MonitoringResource`.

The projection can include `auth_username`, `auth_password`, `http_headers`, and
`http_body` when a monitoring needs them. Treat these as secrets: hold them only
in memory for execution, redact them from logs, and never relay them to another
service.

An instance requesting another location receives `403` with
`{"message":"Unauthorized location"}`.

## Callback payloads

All callbacks require a `monitoring_id` assigned to the authenticated instance.
An unassigned monitoring returns `403` with
`{"message":"Unauthorized monitoring"}`.

| Endpoint | Required fields | Optional fields | Success response |
| --- | --- | --- | --- |
| `POST /monitoring-responses` | `monitoring_id`, `status` | `http_status_code` (100-599), `response_time` (>= 0) | `{"message":"Monitoring response stored successfully."}` |
| `POST /incidents` | `monitoring_id`, `down_at` | — | `{"message":"Incident stored successfully."}` |
| `PUT /incidents/{monitoring}` | `up_at` | — | `{"message":"Incident updated successfully."}` |
| `POST /ssl-results` | `monitoring_id`, `is_valid` | `expires_at`, `issuer`, `issued_at` | `{"message":"SSL result stored successfully."}` |
| `POST /domain-results` | `monitoring_id`, `is_valid` | `expires_at`, `registrar`, `checked_at` | `{"message":"Domain expiration result stored successfully."}` |

`status` must be a valid Core monitoring-status enum. Domain-result callbacks
are valid only for domain-expiration monitorings. Validation failures use
Laravel's `422` JSON validation response.

For monitorings with more than one preferred location, Core manages incident
state through regional consensus. Incident open/close callbacks return `200`
with `{"message":"Incident state is managed by regional consensus."}` and the
instance must not retry them as failed writes. Closing a non-existent open
incident returns `404` with `{"message":"No open incident found."}`.

## Retry and idempotency

The current callback contract does not yet expose an idempotency key. An instance
may retry only transport failures or `5xx` responses with bounded exponential
backoff and jitter. It must not retry `401`, `403`, `422`, or the successful
regional-consensus response. A timeout after a request was sent has ambiguous
delivery semantics; record it safely and use the existing retry policy without
assuming the callback was not persisted.

A later idempotency change requires a new linked Core and instance issue and an
additive contract section before either repository implements it.

## Coordinated rollout checklist

1. Core provides target `/api/v1/internal/instances/*` routes as compatibility
   adapters with identical authentication, validation, status codes, and payloads.
2. Core adds feature tests proving both legacy and target routes behave the same.
3. `webguard-instance` adds configurable base-path support and contract tests,
   tracked in [#35](https://github.com/marcel-breuer/webguard-instance/issues/35).
4. Deploy Core with both paths, then migrate instance deployments to the target
   base URL.
5. Confirm legacy-path traffic has drained and contract tests pass in both
   repositories before scheduling legacy removal.

No Core change may require a scanner implementation change without updating this
document and creating or linking the corresponding `webguard-instance` issue.
