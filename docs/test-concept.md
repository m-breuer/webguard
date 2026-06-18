# Test Concept

This document defines the testing strategy for the WebGuard Management Core & API.
It complements the contribution workflow and should guide feature work, bug fixes,
reviews, and release validation.

## Goals

- Protect monitoring, notification, status page, admin, and API behavior from regressions.
- Keep tests fast enough for local development while still covering production-critical paths.
- Prefer deterministic tests with isolated mail, queue, cache, session, and database state.
- Validate Docker-based development and production assumptions when infrastructure behavior changes.
- Make every user-facing behavior change reviewable through a focused test.

## Test Layers

### Unit Tests

Use unit tests for pure or mostly pure logic in `app/Services`, `app/Support`,
DTO-style payload classes, enum helpers, and small model-related helpers.

Unit tests should:

- Avoid HTTP requests and full browser flows.
- Use explicit input data and assert concrete output values.
- Cover edge cases such as empty histories, unknown statuses, threshold boundaries,
  locale-sensitive labels, and time range boundaries.
- Stay independent from external services.

Relevant location: `tests/Unit`.

### Feature Tests

Use feature tests for Laravel behavior that crosses framework boundaries:
controllers, requests, routes, middleware, policies, database state, jobs,
commands, mail, notifications, API authentication, and public pages.

Feature tests should:

- Assert authorization and data isolation, especially for user-owned monitorings,
  API tokens, admin routes, notification settings, and status pages.
- Cover validation failures as well as successful paths.
- Use factories instead of large fixture files unless a fixture captures a stable protocol contract.
- Assert queued, mailed, notified, logged, and cached behavior with Laravel test fakes.
- Use route names when possible so tests remain stable through URL changes.

Relevant location: `tests/Feature`.

### Browser Tests

Use browser tests only when server-rendered markup, responsive behavior, JavaScript,
or real user interaction is the risk being tested.

Browser tests should cover:

- Navigation and language switching.
- Responsive public and authenticated layouts.
- Interactive monitoring cards, calendars, async tables, dialogs, and theme behavior.
- Critical regressions that cannot be seen through feature tests alone.

Relevant location: `tests/Browser`.

## Test Environment

The default PHP test environment is configured in `phpunit.xml` and `.env.testing`:

- `APP_ENV=testing`
- SQLite in-memory database
- array cache and session drivers
- sync queue driver
- array mail driver

This keeps the standard Pest suite fast and deterministic. Tests that specifically
verify MySQL, Redis, queue worker, or Docker integration behavior must make that
dependency explicit in the test name and setup.

## Docker-First Execution

Run validation inside the project Docker environment whenever technically possible.
Use the local compose stack from `docker-compose.yml` plus `docker-compose.override.yml`.

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php composer test
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php composer analyse
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
```

For targeted backend tests:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php ./vendor/bin/pest tests/Feature/MonitoringExpectedHttpStatusesTest.php
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php ./vendor/bin/pest --filter "expected http statuses"
```

For frontend dependency or build checks:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun install
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
```

Host execution is acceptable only when Docker is unavailable or the repository
explicitly requires a host-specific check. Document that limitation in the final
handoff or pull request.

## Coverage Expectations

Every change should include the narrowest useful validation:

- Service or support logic: unit tests for normal, boundary, and empty-state cases.
- HTTP routes and Blade output: feature tests for status code, authorization,
  visible text, redirects, validation, and persisted state.
- API changes: feature tests for authentication, authorization, request validation,
  response schema, pagination, and backward compatibility.
- Jobs and commands: feature tests for scheduling, dispatch behavior, idempotency,
  retries where applicable, and observable side effects.
- Notifications and mail: feature tests with fakes plus rendering assertions for
  important templates.
- Database migrations: migration compatibility tests when columns, indexes, enums,
  or production database behavior change.
- Frontend scripts and responsive UI: browser tests when behavior depends on
  JavaScript, viewport size, or user interaction.

Bug fixes should include a regression test that fails before the fix and passes
after it.

## Risk Areas

Prioritize tests around these project-specific risks:

- Monitoring state transitions, failure confirmation thresholds, and lifecycle status.
- Response time, uptime, SLA, heatmap, and calendar aggregation calculations.
- Status pages, incident updates, subscribers, and public indexing behavior.
- Notification routing, channel delivery history, digest commands, expiry warnings,
  and unread reminders.
- Heartbeat monitoring and dedicated heartbeat queue behavior.
- API token access, internal instance API compatibility, and public badge/card APIs.
- Admin table filtering, user deletion, package administration, and audit logging.
- Locale, theme, and public legal page rendering.
- Docker deployment settings, Redis extension availability, MySQL compatibility,
  and generated Vite manifest assumptions.

## Test Data Guidelines

- Prefer factories for users, packages, monitorings, responses, incidents, and status pages.
- Freeze time with Laravel helpers when testing date ranges, expiry windows,
  digest periods, and aggregation windows.
- Keep test data minimal but semantically meaningful.
- Avoid external network calls; fake HTTP, mail, notification, queue, and event boundaries.
- Do not depend on test order or previously persisted state.
- Use named constants or helper methods when repeated values represent a business rule.

## Pull Request Validation Matrix

Use this matrix to decide the minimum validation before a change is ready:

| Change type | Minimum validation |
| --- | --- |
| Documentation only | Relevant documentation structure test if links or required topics change |
| Pure service/support logic | Targeted unit tests |
| Route, controller, middleware, request, or Blade change | Targeted feature tests |
| API contract change | Targeted API feature tests plus backward compatibility checks |
| Job, command, scheduler, queue, or notification change | Targeted feature tests for side effects and scheduling |
| Migration, index, or database behavior change | Migration or compatibility test; use MySQL when SQLite cannot model the risk |
| Frontend asset or TypeScript change | `bun run build`; browser test for interaction or responsive behavior |
| Cross-cutting behavior | Targeted tests plus full Pest suite, PHPStan, and frontend build |

## Release Validation

Before a release or broad deployment change, run:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php composer test
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php composer analyse
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
```

Also verify:

- The Docker stack starts successfully with the intended compose profile.
- Migrations run cleanly against the target database engine.
- Queue workers can process the `default` and `heartbeat` queues.
- Mail configuration is validated through Mailpit locally or the configured SMTP provider in staging.
- Public pages, status pages, badges, and API documentation remain reachable.
