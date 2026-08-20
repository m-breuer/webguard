# WebGuard
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

WebGuard is an open-source monitoring core built with Laravel 13. It provides the management UI, REST API, public status pages, notification workflows, and package administration for monitoring websites, DNS records, domains, SSL certificates, and background jobs.

> **System architecture note:** this repository contains the Management Core & API. Distributed scanning nodes live in the [WebGuard Instance Repository](https://github.com/marcel-breuer/webguard-instance), while the mobile client is maintained in the [WebGuard App Repository](https://github.com/marcel-breuer/webguard-app).
## What It Does

- Tracks uptime, response times, expected HTTP status ranges, expected DNS records, SSL expiry, and domain expiry.
- Monitors heartbeats and cron jobs through private ping URLs.
- Classifies multi-location incidents as localized, regional, or global based on selected monitoring locations.
- Paces HTTP and keyword checks at least 15 minutes apart per monitoring location; scanner execution and its compatibility contract live in the [WebGuard Instance Repository](https://github.com/marcel-breuer/webguard-instance).
- Supports team-owned monitorings with group assignment and admin-controlled ownership changes.
- Sends in-app, configurable, expiry, status-change, and weekly digest notifications.
- Provides dashboards, public status pages, SLA badges, and a REST API.
- Supports a global language switch across public and authenticated navigation.

## Quick Start

Requirements: PHP 8.5+, Composer, Bun, a supported database, and Redis.

```bash
composer install
bun install
cp .env.example .env
php artisan key:generate
php artisan migrate
bun run build
composer dev
```

Run tests with:

```bash
php artisan test
```

## Documentation

- [Features](docs/features.md)
- [Architecture and technology stack](docs/architecture.md)
- [Installation, Docker, and operations](docs/installation.md)
- [Test concept](docs/test-concept.md)
- [Contributing](docs/contributing.md)
- [Releases and changelog](docs/releases.md)

## License

WebGuard is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
