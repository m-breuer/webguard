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
