# Architecture and Technology Stack

This repository contains the WebGuard Management Core & API. It owns the user-facing dashboard, admin panel, status pages, notification workflows, API surface, and orchestration logic.

Distributed scanning nodes and workers are maintained separately in the [WebGuard Instance Repository](https://github.com/marcel-breuer/webguard-instance).

## API Boundaries

The API boundary decision and migration rules are documented in the
[API boundaries ADR](architecture/api-boundaries.md). Scanner-instance consumers
must follow the separate [WebGuard Instance API contract](integrations/webguard-instance-api.md).
The stable shared API contract is documented in [Shared API contract](api/external-v1.md).
The SvelteKit browser contract is catalogued in the
[first-party UI API contract](architecture/first-party-ui-contract.md).
The staged migration from stored check statuses to health derived from raw
observations is described in [Derived monitoring health](architecture/derived-monitoring-health.md).
The accepted migration from Blade and Alpine to SvelteKit, including runtime
ownership, route inventory, security boundaries, and rollout gates, is described
in the [SvelteKit frontend migration ADR](architecture/sveltekit-frontend-migration.md).
The controlled release and rollback procedure is documented in the
[SvelteKit production cutover runbook](operations/sveltekit-cutover.md).

## Backend

- **Framework:** Laravel 13 on PHP 8.5+
- **Package manager:** Composer
- **API authentication:** Laravel Sanctum
- **API documentation:** Scribe
- **Cache and queue:** Redis powers high-performance caching and queue-backed asynchronous monitoring tasks.
- **Testing:** Pest and the Pest Browser Plugin

## Frontend

- **Application UI:** SvelteKit with the Node adapter behind the same-origin gateway
- **CSS framework:** Tailwind CSS
- **Laravel rendering:** API, mail, framework-error, signed-link, and fallback ownership
- **Data visualization:** Chart.js
- **Browser API client:** first-party fetch client with Laravel Sanctum CSRF

## Runtime Responsibilities

The core application coordinates monitor configuration, status aggregation, notification delivery, user and package administration, public status output, and API access. Queue workers handle asynchronous monitoring and notification work so the web interface stays responsive.

## Team Ownership

Monitorings are either private (`user_id`) or team-owned (`team_id`). Private monitorings remain visible only to their owner. Team-owned monitorings are visible to all team members, while create, update, delete, reset, and ownership-move actions require a team admin role.

Team notification channels are not shared. Notification channel configuration stays on the user profile, and per-monitoring notification preferences/read states are stored per user so each team member can choose delivery channels independently.
