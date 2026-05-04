# WebGuard

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

WebGuard is an open-source monitoring core built with Laravel 13. It provides the management UI, REST API, public status pages, notification workflows, and package administration for monitoring websites, domains, SSL certificates, and background jobs.

> **System architecture note:** this repository contains the Management Core & API. Distributed scanning nodes live in the [WebGuard Instance Repository](https://github.com/m-breuer/webguard-instance-v2).

## What It Does

- Tracks uptime, response times, expected HTTP status ranges, SSL expiry, and domain expiry.
- Monitors heartbeats and cron jobs through private ping URLs.
- Sends in-app, configurable, expiry, status-change, and weekly digest notifications.
- Provides dashboards, public status pages, an embeddable widget, and a REST API.
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
- [Contributing](docs/contributing.md)

## License

WebGuard is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
