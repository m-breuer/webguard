# Installation and Operations

## Prerequisites

- PHP 8.5 or higher
- Composer
- Bun
- A supported database, usually MySQL for the Docker setup
- Redis for cache and queue processing

## Native Setup Without Docker

Use this setup when you prefer running services directly on your host machine.

1. Clone the repository.

   ```bash
   git clone https://github.com/marcel-breuer/webguard.git
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

In production, run a worker for the configured heartbeat queue on the standard Redis connection. If `HEARTBEAT_QUEUE` is not set, it defaults to `heartbeat`.

```bash
php artisan queue:work redis --queue="${HEARTBEAT_QUEUE:-heartbeat}" --sleep=3 --tries=3 --max-time=3600
```

The Docker worker process handles `default,heartbeat` by default.

## Docker Deployment Model

This repository is platform-neutral. It does not assume a specific hosting platform. The Docker stack can be run directly through Docker Compose, an Ansible-managed server, Portainer, Docker Swarm with adapted stack files, or another reverse-proxy-based deployment environment.

The standard deployment stack contains:

- `php`: web runtime with the application server
- `schedule`: Laravel scheduler process
- `queue-default`: Redis-backed queue worker for `default,heartbeat`

The optional `internal-services` profile also adds bundled infrastructure for local or standalone deployments:

- `mysql`
- `redis`

For production, prefer externally managed shared infrastructure if this app runs together with other projects on the same server. In that case, the production infrastructure repository should provide MySQL, Redis, Traefik, backups, and secrets, while this repository only provides the application image and local development stack.

## Minimum Production Environment

Use `.env.example` as the starting point, but set concrete production values. Do not reference one environment variable from another inside the value because Docker Compose evaluates the environment file before the container starts.

```env
APP_URL=https://webguard.example.com
APP_KEY=base64:...
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

Optional production values:

- `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` for GitHub login
- `IMPRINT_*` fields for legal/imprint content
- `FCM_*` and `APNS_*` values for push notification integrations

For SMTP on port `465`, set `MAIL_ENCRYPTION=ssl`. For SMTP on port `587`, set `MAIL_ENCRYPTION=tls`.

## Reverse Proxy Operation

The application listens internally on port `8080` for HTTP and `8443` for optional container-level HTTPS. In normal production operation, terminate public TLS at the reverse proxy and route traffic to the `php` service on port `8080`.

Recommended production setup:

```text
Internet
  -> Traefik/Caddy/Nginx :443
  -> webguard php service :8080
```

Set:

```env
DOCKER_SSL_MODE=off
```

Use container-level HTTPS on `8443` only when the connection between the reverse proxy and the application container must also be encrypted.

## External Database and Redis

For production deployments with shared infrastructure, set `DB_HOST` and `REDIS_HOST` to the service names or hostnames of the database and Redis containers reachable through the shared Docker network.

Example with external services in the same Docker network:

```env
WEBGUARD_NETWORK=edge-or-data-network

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=webguard_core
DB_USERNAME=webguard
DB_PASSWORD=...

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
```

`WEBGUARD_NETWORK` must be the existing external Docker network shared by WebGuard, MySQL, Redis, and any `webguard-instance` containers. For a one-server setup with a central infrastructure repository, this network should be created and owned by that infrastructure repository.

The bundled `mysql` and `redis` services are behind the `internal-services` Compose profile. Set `COMPOSE_PROFILES=internal-services` only when this Compose stack should also create the bundled MySQL and Redis containers.

## Build and Startup Performance

The production Dockerfile uses BuildKit cache mounts for Composer and Bun dependency downloads. Keep BuildKit enabled in Docker, Docker Compose, or the CI pipeline so repeated builds can reuse those caches.

The production web container keeps startup-generated artifacts disabled by default to make deploys and restarts faster:

```env
AUTORUN_LARAVEL_SITEMAP_GENERATE=false
AUTORUN_LARAVEL_SCRIBE_GENERATE=false
```

Set either value to `true` only when the container should regenerate the static sitemap or Scribe API documentation during startup.

## Docker Local Development

The local override adds everything that should only exist during development:

- local Traefik
- Bun/Vite dev server
- MySQL
- Redis
- Mailpit
- bind mounts for the application code

### Local Setup

1. Clone and enter the repository.

   ```bash
   git clone https://github.com/marcel-breuer/webguard.git
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
AUTORUN_LARAVEL_SITEMAP_GENERATE=false
AUTORUN_LARAVEL_SCRIBE_GENERATE=false
```

If you already have an older `.env`, align it with `.env.example` before using Docker.

### Local Commands

Run migrations:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
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

The local stack uses the shared Docker network `webguard-network`. Because the local Traefik service also has the network alias `webguard.test`, other containers on the same network can reach WebGuard through the same URL as your browser.

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
