# Architecture and Technology Stack

This repository contains the WebGuard Management Core & API. It owns the user-facing dashboard, admin panel, status pages, notification workflows, API surface, and orchestration logic.

Distributed scanning nodes and workers are maintained separately in the [WebGuard Instance Repository](https://github.com/marcel-breuer/webguard-instance).

## Backend

- **Framework:** Laravel 13 on PHP 8.5+
- **Package manager:** Composer
- **API authentication:** Laravel Sanctum
- **API documentation:** Scribe
- **Social authentication:** Laravel Socialite is installed for future social login integrations and currently configured for GitHub.
- **Cache and queue:** Redis powers high-performance caching and queue-backed asynchronous monitoring tasks.
- **Testing:** Pest and the Pest Browser Plugin

## Frontend

- **Build tool:** Vite
- **CSS framework:** Tailwind CSS
- **Reactive components:** Alpine.js
- **Data visualization:** Chart.js
- **HTTP requests:** Axios

## Runtime Responsibilities

The core application coordinates monitor configuration, status aggregation, notification delivery, user and package administration, public status output, and API access. Queue workers handle asynchronous monitoring and notification work so the web interface stays responsive.

## Team Ownership

Monitorings are either private (`user_id`) or team-owned (`team_id`). Private monitorings remain visible only to their owner. Team-owned monitorings are visible to all team members, while create, update, delete, reset, and ownership-move actions require a team admin role.

Team notification channels are not shared. Notification channel configuration stays on the user profile, and per-monitoring notification preferences/read states are stored per user so each team member can choose delivery channels independently.
