# Installation and Operations

## Prerequisites

- PHP 8.5 or higher
- Composer
- Bun
- A supported database, such as MySQL or PostgreSQL
- Redis

## Native Setup Without Docker

Use this setup when you prefer running services directly on your host machine.

1. Clone the repository.

   ```bash
   git clone https://github.com/m-breuer/webguard.git
   cd webguard
   ```

2. Install dependencies.

   ```bash
   composer install
   bun install
   ```

3. Create and configure the environment file.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   For a native setup, change `DB_HOST`, `REDIS_HOST`, `CACHE_STORE`, and `QUEUE_CONNECTION` away from the Docker defaults from `.env.example`.

4. Run migrations.

   ```bash
   php artisan migrate
   ```

5. Build frontend assets.

   ```bash
   bun run build
   ```

6. Run development processes.

   ```bash
   composer dev
   ```

   The development command starts the Laravel development server, the default queue worker, the dedicated Redis-backed `heartbeat` queue worker, the Pail log viewer, and the Vite development server.

7. Run the test suite.

   ```bash
   php artisan test
   ```

## Heartbeat Queue Worker

In production, run a dedicated worker for the configured heartbeat queue on the standard Redis connection. If `HEARTBEAT_QUEUE` is not set, it defaults to `heartbeat`.

```bash
php artisan queue:work redis --queue="${HEARTBEAT_QUEUE:-heartbeat}" --sleep=3 --tries=3 --max-time=3600
```

Docker worker processes handle `default,heartbeat` by default.

## Docker Deployment

This repository uses two Docker modes:

- `docker-compose.yml`: standard deployment stack
- `docker-compose.override.yml`: local development additions only

The standard deployment stack always contains:

- `php`
- `schedule`
- `queue-default`

The optional `internal-services` profile also adds bundled infrastructure:

- `mysql`
- `redis`

Use `.env.example` as the starting point for `.env`.

Minimum `.env` fields for production:

```env
SERVICE_URL_PHP=https://webguard.example.com
APP_KEY=base64:...
WEBGUARD_NETWORK=coolify
DOCKER_SSL_MODE=off

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=webguard_core
DB_USERNAME=webguard
DB_PASSWORD=super-secret-password

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_USERNAME=null
REDIS_PASSWORD=null

MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
SMTP_USERNAME=mailer-user
MAIL_PASSWORD=mailer-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=WebGuard
```

When using Coolify, set concrete values instead of referencing another variable inside the value. For example, use `SERVICE_URL_PHP=https://webguard.example.com`, `SMTP_USERNAME=noreply@example.com`, `MAIL_FROM_NAME=WebGuard`, and `GITHUB_REDIRECT_URI=https://webguard.example.com/auth/github/callback`. Do not use values such as `APP_URL={$SERVICE_URL_PHP}` or `MAIL_USERNAME=${MAIL_FROM_ADDRESS}` because Docker Compose evaluates the env file during build and can warn or substitute empty strings before the container starts.

For Docker deployments, use `SMTP_USERNAME` as the input variable. The Compose file passes it into the container as Laravel's `MAIL_USERNAME`. This avoids Docker Compose expanding old values such as `MAIL_USERNAME=${MAIL_FROM_ADDRESS}` during Coolify builds.

If Coolify still shows `MAIL_USERNAME`, delete it or replace it with a literal value. Do not keep `MAIL_USERNAME=${MAIL_FROM_ADDRESS}` in Coolify; Docker Compose expands the env file before the application starts and logs a warning when the referenced value is not available at that point.

For SMTP on port `465`, set `MAIL_ENCRYPTION=ssl`. For SMTP on port `587`, set `MAIL_ENCRYPTION=tls`.

Optional:

- `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` for GitHub login
- `MARKETING_URL` for an optional link to a separately deployed marketing website
- `MARKETING_LEGAL_URL` for optional canonical legal pages on the separately deployed marketing website; the application appends `/imprint`, `/terms-of-use`, and `/gdpr`
- `IMPRINT_*` fields for legal/imprint content

### Build and Startup Performance

The production Dockerfile uses BuildKit cache mounts for Composer and Bun dependency downloads. Keep BuildKit enabled in Docker, Docker Compose, or your deployment platform so repeated builds can reuse those caches.

The production web container regenerates `robots.txt` from `APP_URL` on startup. Optional sitemap and Scribe generation remain disabled by default to keep deploys and restarts faster:

```env
AUTORUN_LARAVEL_ROBOTS_GENERATE=true
AUTORUN_LARAVEL_SITEMAP_GENERATE=false
AUTORUN_LARAVEL_SCRIBE_GENERATE=false
```

The robots file is regenerated from `APP_URL` during startup by default. Set `AUTORUN_LARAVEL_ROBOTS_GENERATE=false` only when you manage that file separately. Set the sitemap or Scribe flags to `true` when the container should regenerate those static artifacts during startup. The entrypoint scripts remain installed in the image and skip work unless the matching flag is enabled.

### External Database and Redis

For production deployments such as Coolify, set `DB_HOST` and `REDIS_HOST` to the service names or hostnames of the database and Redis containers you want to use.

Example with external services in the same Docker network:

```env
WEBGUARD_NETWORK=coolify

DB_HOST=my-production-mysql
DB_PORT=3306
DB_DATABASE=webguard_core
DB_USERNAME=webguard
DB_PASSWORD=...

REDIS_HOST=my-production-redis
REDIS_PORT=6379
REDIS_PASSWORD=null
```

`WEBGUARD_NETWORK` must be the existing external Docker network shared by WebGuard, MySQL, Redis, and any webguard-instance containers. In Coolify this is usually `coolify`; use the actual shared network name from your server if it differs.

The bundled `mysql` and `redis` services are behind the `internal-services` Compose profile. Leave `COMPOSE_PROFILES` empty in Coolify when you connect external services. Set `COMPOSE_PROFILES=internal-services` only when this Compose stack should also create the bundled MySQL and Redis containers.

Coolify detects variables that are referenced in `docker-compose.yml` and exposes them in its UI. Keep production secrets as runtime variables in Coolify and override `DB_HOST`, `DB_PASSWORD`, `REDIS_HOST`, `REDIS_PASSWORD`, and mail credentials there.

For Coolify routing, assign the public domain to the `php` service in Coolify and route it to internal port `8080`; for example, use `https://webguard-test.m-breuer.dev:8080` in the Coolify domain field. Set `SERVICE_URL_PHP` to the same public URL without the internal port, for example `https://webguard-test.m-breuer.dev`, so Laravel generates correct absolute URLs.

Do not add custom Traefik labels that reference `SERVICE_FQDN_PHP` or Docker Compose defaults such as `${SERVICE_FQDN_PHP:-webguard.example.com}` in Coolify. Coolify generates the proxy routing and Let's Encrypt labels from the assigned domain; shipping custom labels can leave unresolved placeholders in Traefik and cause invalid `HostSNI` or ACME identifiers.

The application listens internally on `8080` for HTTP and `8443` for optional container-level HTTPS. For Coolify deployments, keep `DOCKER_SSL_MODE=off` and let Coolify/Traefik generate and terminate public TLS.

If you use Coolify, Traefik, or another reverse proxy in front of the deployment, route traffic to the `php` service on `8080`. Set `DOCKER_SSL_MODE=mixed` and use `8443` only when you explicitly want encrypted traffic between the proxy and the application container.

## Docker Local Development

The local override adds everything that should only exist during development:

- Traefik
- Bun / Vite
- MySQL
- Redis
- Mailpit
- bind mounts for the application code

### Local Setup

1. Clone and enter the repository.

   ```bash
   git clone https://github.com/m-breuer/webguard.git
   cd webguard
   ```

2. Create your local environment file.

   ```bash
   cp .env.example .env
   ```

3. Add a hosts entry for the local domain.

   ```text
   127.0.0.1 webguard.test
   127.0.0.1 mailpit.webguard.test
   ```

4. Start the local stack.

   ```bash
   ./start-dev.sh
   ```

5. Initialize Laravel once.

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan key:generate
   docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
   ```

`start-dev.sh` builds the local stack, starts it, and opens a shell inside the `php` container.

### Local URLs

- App: [http://webguard.test](http://webguard.test)
- HTTPS app: [https://webguard.test](https://webguard.test)
- Vite: [http://webguard.test:5173](http://webguard.test:5173)
- Mailpit UI: [http://mailpit.webguard.test](http://mailpit.webguard.test)

### Local Environment Values

`.env.example` is the only Docker template.
Minimum `.env` fields for local Docker:

```env
APP_NAME=WebGuard
APP_ENV=local
APP_DEBUG=true
APP_URL=http://webguard.test
MARKETING_URL=
APP_KEY=base64:...
APP_TIMEZONE=Europe/Berlin
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=webguard_core
DB_USERNAME=webguard
DB_PASSWORD=webguard

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_USERNAME=null
REDIS_PASSWORD=null
HEARTBEAT_QUEUE=heartbeat
MONITORING_REGIONAL_CONSENSUS_FRESHNESS_MINUTES=10

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_USERNAME=null
SMTP_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=noreply@webguard.test
MAIL_FROM_NAME=WebGuard

VITE_DEV_SERVER_URL=http://webguard.test:5173
VITE_HMR_HOST=webguard.test

DOCKER_APP_HOST=webguard.test
DOCKER_MAILPIT_HOST=mailpit.webguard.test
DOCKER_HTTP_PORT=80
DOCKER_HTTPS_PORT=443
DOCKER_VITE_PORT=5173
DOCKER_MYSQL_PORT=3306
DOCKER_MAILPIT_SMTP_PORT=1025
DOCKER_MAILPIT_UI_PORT=8025
DOCKER_SSL_MODE=off
WEBGUARD_NETWORK=webguard-network
COMPOSE_PROFILES=internal-services
AUTORUN_LARAVEL_ROBOTS_GENERATE=true
AUTORUN_LARAVEL_SITEMAP_GENERATE=false
AUTORUN_LARAVEL_SCRIBE_GENERATE=false
```

If you already have an older `.env`, align it with `.env.example` before using Docker.

### Local Commands

Run migrations:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
```

Regenerate `public/robots.txt` from the configured `APP_URL`:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan robots:generate
```

Run one queue job:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec queue-default php artisan queue:work redis --once
```

Build frontend assets:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
```

Install frontend dependencies:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun install
```

## webguard-instance Integration With Local Docker

The local stack uses the shared Docker network `webguard-network`.
Because the local Traefik service also has the network alias `webguard.test`, other containers on the same network can reach WebGuard through the same URL as your browser.

That means `webguard-instance` can use either:

- `http://webguard.test/api/v1/internal`
- `http://webguard-core/api/v1/internal`

Example:

```yaml
services:
  webguard-instance:
    networks:
      - webguard-network
    environment:
      WEBGUARD_CORE_API_URL: http://webguard.test/api/v1/internal

networks:
  webguard-network:
    external: true
```
