# Derived monitoring health

**Status:** Accepted migration plan.  
**Tracking:** [WebGuard Core #632](https://github.com/marcel-breuer/webguard/issues/632), [webguard-instance #42](https://github.com/marcel-breuer/webguard-instance/issues/42)

## Decision

The Core treats raw check evidence as the source of truth and derives availability from the monitoring type and configuration. `up`, `down`, and `unknown` remain public availability values; `degraded` is a separate private performance state. A slow but reachable service is therefore `up` and may also be `degraded`.

The legacy `monitoring_response_results.status` value is nullable during migration. It is only a compatibility fallback for existing scanners and historical records. New writers must omit it when they provide sufficient raw evidence. The column is not removed in this release.

## Raw observation contract

The authenticated scanner endpoint accepts the existing fields plus optional `vital_values`:

| Monitoring type | Required new evidence | Derived availability |
| --- | --- | --- |
| HTTP / keyword | HTTP status code, or `transport_succeeded: false` with a failure reason | Expected HTTP-status ranges decide `up`/`down`; a transport failure is `down`. |
| Ping / port | `connection_succeeded` | `true` is `up`; `false` is `down`. |
| DNS record | `observed_values` | The Core compares observed and configured expected values. |
| Heartbeat | `heartbeat_received` or `heartbeat_overdue` | Received is `up`; overdue is `down`. |
| Server health | CPU, RAM, or storage metrics | Configured thresholds decide `up`/`down`. |

If the Core cannot derive a status and no legacy status is present, it returns `unknown`. Scanner responses must never include an invented response time for a failed transport request.

## Performance state

HTTP and keyword monitorings may configure `response_time_threshold_ms` and `response_time_confirmation_threshold`. A successful response at or above the threshold increments the consecutive breach count. When the count reaches the threshold, Core records a private `degraded` event. The first subsequent successful response below the threshold records recovery. These events use the selected monitoring notification channels but never create public status-page updates or uptime incidents.

## Rollout order

1. Deploy this Core release, which accepts legacy status writes and raw observations.
2. Implement and contract-test raw observation writes in `webguard-instance`.
3. Monitor dual-read behavior and backfill only where historical raw evidence exists.
4. Make raw evidence mandatory in a future major instance-contract revision.
5. Remove legacy status columns only after all supported scanners and historical read paths have migrated.

## Compatibility and idempotency

The instance route family remains `/api/instances/*`; its authentication and retry behavior do not change. A repeated observation can be stored as before; degradation notifications are deduplicated by the persisted performance state.
