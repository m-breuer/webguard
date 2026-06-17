# Agent Context

These instructions apply to all coding agents working in this repository.

## Project Scope

WebGuard Core is the Laravel Management Core & API for WebGuard. It owns the
dashboard, admin panel, public status pages, REST API, monitoring orchestration,
notification workflows, package administration, and legal/public content.

Distributed scanning nodes are maintained in the separate `webguard-instance`
repository. Do not implement instance-node behavior in this repository unless
the core API contract or integration surface explicitly requires it.

## Technology Context

- Backend: Laravel 13 on PHP 8.5+.
- Tests: Pest, PHPUnit configuration in `phpunit.xml`, Pest Browser Plugin.
- Frontend: Vite, Tailwind CSS, Alpine.js, TypeScript, Chart.js.
- Runtime services: MySQL-compatible database, Redis cache and queues, SMTP mail.
- Local Docker: `docker-compose.yml` plus `docker-compose.override.yml`.
- Local helper: `./start-dev.sh` starts the development stack and opens a PHP container shell.

## Required Workflow

1. Inspect the current worktree before editing.
2. Read the relevant application, test, and documentation files before making changes.
3. Keep changes focused on the requested behavior.
4. Preserve unrelated user changes; do not revert files you did not intentionally change.
5. Prefer existing Laravel, Pest, Blade, service, DTO, enum, factory, and route patterns.
6. Add or update tests for new behavior and regressions.
7. Update documentation when user-facing behavior, setup, deployment, or testing expectations change.
8. Do not commit secrets, generated local artifacts, dependency directories, logs, or machine-specific files.

## Docker-First Commands

Work inside Docker whenever technically possible. Prefer project-defined Docker
configuration over host-global tools.

Use this compose command shape:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml ...
```

Common commands:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php composer test
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php composer analyse
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun install
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
```

Use host commands only when Docker is unavailable or a task explicitly requires
host execution. Record that exception in the handoff.

## Testing Instructions

Follow `docs/test-concept.md`.

Use:

- `tests/Unit` for services, support classes, DTO-style payload logic, enum helpers,
  and deterministic calculations.
- `tests/Feature` for routes, controllers, requests, middleware, commands, jobs,
  mail, notifications, database behavior, API contracts, and authorization.
- `tests/Browser` for rendered UI, responsive layout, JavaScript interactions,
  and behavior that cannot be validated through feature tests.

The default suite uses SQLite in-memory, array mail/cache/session drivers, and
sync queues. Tests that require MySQL, Redis, Docker, or queue workers must make
that dependency explicit.

For targeted validation, run the smallest relevant Pest file or filter first.
For cross-cutting changes, run the full Pest suite, PHPStan, and the frontend build.

## Code Standards

- Keep PHP files strict typed where the surrounding code uses `declare(strict_types=1);`.
- Use Laravel conventions for validation, authorization, routing, queues, mail,
  notifications, factories, casts, and configuration.
- Keep controllers thin; put reusable business logic in services or support classes.
- Prefer named route generation over hard-coded application URLs.
- Prefer factories and Laravel test fakes over broad fixtures or live integrations.
- Avoid external network access in tests.
- Keep Blade markup accessible, localized where appropriate, and consistent with existing components.
- Keep TypeScript behavior small, typed, and tied to existing component patterns.

## Quality Gates

Before handing off a change, run the relevant checks inside Docker when possible:

- Targeted Pest tests for changed behavior.
- `composer test` for broad backend changes.
- `composer analyse` for PHP type/static-analysis risk.
- `bun run build` for frontend or asset changes.

If a check cannot be run, state why and identify the remaining risk.

## Documentation Standards

- Keep root `README.md` concise and link expanded documentation from `docs/`.
- Put testing strategy in `docs/test-concept.md`.
- Put setup and Docker operations in `docs/installation.md`.
- Put contribution workflow in `docs/contributing.md`.
- Keep documentation language factual and repository-specific.
- Keep agent-specific adapter files minimal and point them back to this file.
