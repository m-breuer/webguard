# WebGuard

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> 💡 **System Architecture Note:** This repository contains the **Management Core & API**. For the distributed scanning node/worker, please visit the [WebGuard Instance Repository](https://github.com/m-breuer/webguard-instance-v2).

WebGuard is a powerful, open-source web monitoring service built with Laravel 13. It's designed to help you track website uptime, response times, SSL certificate statuses, and domain registration expiry with ease. Whether you're a developer, a small business owner, or a system administrator, WebGuard provides the tools you need to ensure your online services are running smoothly.

The application features a user-friendly dashboard for at-a-glance statistics, a comprehensive admin panel for user and package management, and a REST API for programmatic access and integration with other systems.

## Key Features

* **Uptime Monitoring:** Keep a close eye on your website's availability with asynchronous uptime checks.
* **Heartbeat & Cron Monitoring:** Detect stalled cron jobs, workers, and background tasks with private heartbeat ping URLs.
* **Response Time Tracking:** Monitor your website's performance by tracking response times.
* **Expected HTTP Status Ranges:** Define accepted HTTP status codes or ranges, such as `200-299, 301, 302`, per HTTP or keyword monitoring.
* **SSL Certificate Monitoring:** Get notified before your SSL certificates expire, so you can renew them in time.
* **Domain Expiration Monitoring:** Track domain registration expiry and receive proactive renewal warnings before critical domains lapse.
* **Customizable Checks:** Configure HTTP method, body, and headers for your monitoring checks.
* **Real-Time Dashboard:** Visualize your monitoring data with real-time statistics and charts.
* **Admin Panel:** Manage users, subscription packages, and review API usage logs.
* **REST API:** Programmatically access your monitoring data and integrate WebGuard with your existing workflows.
* **Embeddable Widget:** Display your website's monitoring status on external sites with a simple JavaScript widget.
* **Flexible Notifications:** Receive notifications for status changes, SSL expiry, and domain expiry via in-app notifications and configurable channels.
* **Weekly Monitoring Digest:** Email weekly uptime, incident, downtime, SSL, and domain expiry summaries to active users.
* **Public Status Pages:** Create public status pages for your monitorings to keep your users informed.
* **Global Language Switch:** Switch between supported languages from both public and authenticated top navigation.
* **Landing Navigation Anchors:** Landing-page menu links resolve correctly to homepage sections, even when clicked from other routes.

## Core Technologies

### Backend

* **Framework:** Laravel 13 (PHP 8.4+) - *Chosen for robust MVC architecture and modern PHP features.*
* **Package Manager:** Composer
*   **API Authentication:** Laravel Sanctum
*   **API Documentation:** Scribe
*   **Social Authentication (Future):** Laravel Socialite - *Installed for future social login integrations, currently configured for GitHub.*
*   **Cache & Queue:** Redis - *Utilized for high-performance caching and efficient queue management for asynchronous monitoring tasks, ensuring minimal latency.*
*   **Testing:** Pest + Pest Browser Plugin

### Frontend

* **Build Tool:** Vite
* **CSS Framework:** Tailwind CSS
* **JavaScript:**
    * **Reactive Components:** Alpine.js
    * **Data Visualization:** Chart.js
    * **HTTP Requests:** Axios

## Docker Deployment

This repository now uses two Docker modes:

* `docker-compose.yml`: standard deployment stack
* `docker-compose.override.yml`: local development additions only

The standard deployment stack always contains:

* `php`
* `schedule`
* `queue-default`

The optional `internal-services` profile also adds bundled infrastructure:

* `mysql`
* `redis`

Use `.env.example` as the starting point for `.env`.
Minimum `.env` fields for production:

```env
APP_URL=https://webguard.example.com
APP_KEY=base64:...

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
MAIL_USERNAME=mailer-user
MAIL_PASSWORD=mailer-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=WebGuard
```

When using Coolify, set concrete values instead of referencing another variable inside the value. For example, use `APP_URL=https://webguard.example.com`, `MAIL_USERNAME=noreply@example.com`, `MAIL_FROM_NAME=WebGuard`, and `GITHUB_REDIRECT_URI=https://webguard.example.com/auth/github/callback`. Do not use values such as `APP_URL={$SERVICE_URL_PHP}` or `MAIL_USERNAME=${MAIL_FROM_ADDRESS}` because Docker Compose evaluates the env file during build and can warn or substitute empty strings before the container starts.

For SMTP on port `465`, set `MAIL_ENCRYPTION=ssl`. For SMTP on port `587`, set `MAIL_ENCRYPTION=tls`.

Optional:
* `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` for GitHub login
* `IMPRINT_*` fields for legal/imprint content

### External database and Redis

For production deployments such as Coolify, set `DB_HOST` and `REDIS_HOST` to the service names or hostnames of the database and Redis containers you want to use.

Example with external services in the same Docker network:

```env
DB_HOST=my-production-mysql
DB_PORT=3306
DB_DATABASE=webguard_core
DB_USERNAME=webguard
DB_PASSWORD=...

REDIS_HOST=my-production-redis
REDIS_PORT=6379
REDIS_PASSWORD=null
```

The bundled `mysql` and `redis` services are behind the `internal-services` Compose profile. Leave `COMPOSE_PROFILES` empty in Coolify when you connect external services. Set `COMPOSE_PROFILES=internal-services` only when this Compose stack should also create the bundled MySQL and Redis containers.

Coolify detects variables that are referenced in `docker-compose.yml` and exposes them in its UI. Keep production secrets as runtime variables in Coolify and override `DB_HOST`, `DB_PASSWORD`, `REDIS_HOST`, `REDIS_PASSWORD`, and mail credentials there.

The application listens internally on `8080` for HTTP and `8443` for HTTPS. Production enables `SSL_MODE=mixed`, so the ServerSideUp PHP image generates a self-signed certificate automatically when no custom certificate is mounted.

If you use Coolify, Traefik, or another reverse proxy in front of the deployment, you can route traffic to the `php` service on `8080` and let the proxy terminate public TLS. Use `8443` only when you explicitly want encrypted traffic between the proxy and the application container.

## Docker Local Development

The local override adds everything that should only exist during development:

* Traefik
* Bun / Vite
* MySQL
* Redis
* Mailpit
* bind mounts for the application code

### Local setup

1. Clone and enter the repository:

   ```bash
   git clone https://github.com/m-breuer/webguard.git
   cd webguard
   ```

2. Create your local environment file:

   ```bash
   cp .env.example .env
   ```

3. Add a hosts entry for the local domain:

   ```text
   127.0.0.1 webguard.test
   127.0.0.1 mailpit.webguard.test
   ```

4. Start the local stack:

   ```bash
   ./start-dev.sh
   ```

5. Initialize Laravel once:

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan key:generate
   docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
   ```

`start-dev.sh` builds the local stack, starts it, and opens a shell inside the `php` container.

### Local URLs

* App: [http://webguard.test](http://webguard.test)
* HTTPS app: [https://webguard.test](https://webguard.test)
* Vite: [http://webguard.test:5173](http://webguard.test:5173)
* Mailpit UI: [http://mailpit.webguard.test](http://mailpit.webguard.test)

### Local environment values

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
COMPOSE_PROFILES=internal-services
```

If you already have an older `.env`, align it with `.env.example` before using Docker.

### Local commands

* Run migrations:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
  ```

* Run one queue job:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml exec queue-default php artisan queue:work redis --once
  ```

* Build frontend assets:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
  ```

* Install frontend dependencies:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun install
  ```

## webguard-instance Integration (Local Docker)

The local stack uses the shared Docker network `webguard-network`.
Because the local Traefik service also has the network alias `webguard.test`, other containers on the same network can reach WebGuard through the same URL as your browser.

That means `webguard-instance` can use either:

* `http://webguard.test/api/v1/internal`
* `http://webguard-core/api/v1/internal`

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

## Native Setup (Without Docker)

If you prefer running services directly on your host machine, use the classic Laravel setup:

1. Install dependencies:

   ```bash
   composer install
   bun install
   ```

2. Configure `.env` and generate an app key:

   ```bash
   php artisan key:generate
   ```

   For a native setup, change `DB_HOST`, `REDIS_HOST`, `CACHE_STORE`, and `QUEUE_CONNECTION` away from the Docker defaults from `.env.example`.

3. Run migrations:

   ```bash
   php artisan migrate
   ```

4. Run development processes:

   ```bash
   bun run dev
   ```

   This starts the Laravel development server, the Redis-backed queue workers, the Pail log viewer, and the Vite development server.

   In production, ensure the configured heartbeat queue is processed as well. The Docker worker processes `default,heartbeat` by default.

8.  **Run the test suite:**

    ```bash
    php artisan test
    ```

## Contributing

We welcome contributions from the community! If you'd like to contribute to WebGuard, please follow these steps:

1.  Fork the repository.
2.  Create a new branch for your feature or bug fix: `git checkout -b feature-or-bugfix-name`.
3.  Make your changes and commit them with a descriptive commit message (adhering to Conventional Commits).
4.  Push your changes to your forked repository.
5.  Create a pull request to the `main` branch of the original repository.

Please make sure to write tests for your changes and ensure that the existing test suite passes.

## License

WebGuard is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
