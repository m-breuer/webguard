# WebGuard Instance Follow-Up

This core project now records `server_instances.last_seen_at` whenever a scanner instance successfully authenticates against the internal API.

The Go `webguard-instance` project does not need a new endpoint for this feature. It should keep sending the existing `X-INSTANCE-CODE` and `X-API-KEY` headers on every internal API request, especially when polling `GET /api/v1/internal/monitorings`.

Recommended checks in the Go project:

- Confirm the polling loop calls the internal monitoring list endpoint at least once per configured monitoring interval.
- Confirm all internal result submission requests include the same instance authentication headers.
- Add or update an integration test that fails when those headers are missing from polling or result-submission requests.
- If the instance has a backoff/retry loop, ensure normal retries do not stop polling indefinitely without logging a clear operational error.
