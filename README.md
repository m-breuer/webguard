# WebGuard

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> 💡 **System Architecture Note:** This repository contains the **Management Core & API**. For the distributed scanning node/worker, please visit the [WebGuard Instance Repository](https://github.com/m-breuer/webguard-instance-v2).

WebGuard is a powerful, open-source web monitoring service built with Laravel 12. It's designed to help you track website uptime, response times, and SSL certificate statuses with ease. Whether you're a developer, a small business owner, or a system administrator, WebGuard provides the tools you need to ensure your online services are running smoothly.

The application features a user-friendly dashboard for at-a-glance statistics, a comprehensive admin panel for user and package management, and a REST API for programmatic access and integration with other systems.

## Key Features

* **Uptime Monitoring:** Keep a close eye on your website's availability with asynchronous uptime checks.
* **Response Time Tracking:** Monitor your website's performance by tracking response times.
* **SSL Certificate Monitoring:** Get notified before your SSL certificates expire, so you can renew them in time.
* **Customizable Checks:** Configure HTTP method, body, and headers for your monitoring checks.
* **Real-Time Dashboard:** Visualize your monitoring data with real-time statistics and charts.
* **Admin Panel:** Manage users, subscription packages, and review API usage logs.
* **REST API:** Programmatically access your monitoring data and integrate WebGuard with your existing workflows.
* **Embeddable Widget:** Display your website's monitoring status on external sites with a simple JavaScript widget.
* **Flexible Notifications:** Receive notifications for status changes and SSL expiry via in-app notifications and email.
* **Public Status Pages:** Create public status pages for your monitorings to keep your users informed.

## Core Technologies

### Backend

* **Framework:** Laravel 12 (PHP 8.4) - *Chosen for robust MVC architecture and modern PHP features.*
* **Package Manager:** Composer
*   **API Authentication:** Laravel Sanctum
*   **API Documentation:** Scribe
*   **Social Authentication (Future):** Laravel Socialite - *Installed for future social login integrations, currently configured for GitHub.*
*   **Cache & Queue:** Redis - *Utilized for high-performance caching and efficient queue management for asynchronous monitoring tasks, ensuring minimal latency.*

### Frontend

* **Build Tool:** Vite
* **CSS Framework:** Tailwind CSS
* **JavaScript:**
    * **Reactive Components:** Alpine.js
    * **Data Visualization:** Chart.js
    * **HTTP Requests:** Axios

## Docker Local Development (Recommended)

The repository includes a Docker development stack with:

* Traefik reverse proxy (domain routing + optional HTTPS on `:443`)
* `serversideup/php:8.5-fpm-nginx` for Laravel app serving
* Redis for queues and caching
* MySQL
* Bun service for Vite and frontend builds
* Dedicated worker container with `serversideup/php:8.5-cli`

### Prerequisites

* Docker with Docker Compose plugin

### Start the environment

1. **Clone and enter the repository**

   ```bash
   git clone https://github.com/m-breuer/webguard.git
   cd webguard
   ```

2. **Prepare your app environment file**

   ```bash
   cp .env.example .env
   ```

   Docker-specific defaults (Redis queue/cache, MySQL host, internal API URL) are in `.env.docker`.
   Adjust that file if you need different local values.

### Environment Variables For Docker

WebGuard Docker reads both files:

* `.env` (Laravel base config)
* `.env.docker` (Docker overrides; loaded after `.env`)

`docker-compose.yml` interpolation (`${...}` in image tags, ports, hostnames, and MySQL container values) reads from `.env`.

Set these values before first start:

* Required in `.env`:
  * `APP_NAME=WebGuard`
  * `APP_URL=http://webguard.localhost`
  * `APP_KEY=` (leave empty first, then run `php artisan key:generate` once)
  * `DOCKER_APP_HOST=webguard.localhost`
  * `DOCKER_HTTP_PORT=80`
  * `DOCKER_HTTPS_PORT=443`
  * `DOCKER_VITE_PORT=5173`

* Required Docker runtime values (already provided in `.env.docker`):
  * `DB_CONNECTION=mysql`
  * `DB_HOST=mysql`
  * `DB_PORT=3306`
  * `DB_DATABASE=webguard_core`
  * `DB_USERNAME=webguard`
  * `DB_PASSWORD=webguard`
  * `CACHE_STORE=redis`
  * `QUEUE_CONNECTION=redis`
  * `REDIS_HOST=redis`
  * `REDIS_PORT=6379`

* Required in `.env` for the MySQL container itself:
  * `DOCKER_MYSQL_DATABASE=webguard_core`
  * `DOCKER_MYSQL_USER=webguard`
  * `DOCKER_MYSQL_PASSWORD=webguard`
  * `DOCKER_MYSQL_ROOT_PASSWORD=root`
  * `DOCKER_MYSQL_PORT=3306`

* Optional integration values in `.env.docker` (for `webguard-instance`):
  * `WEBGUARD_CORE_INTERNAL_API_URL=http://webguard-core/api/v1/internal`
  * `WEBGUARD_INSTANCE_CODE=...`
  * `WEBGUARD_INSTANCE_API_KEY=...`

3. **Start everything with one command**

   ```bash
   ./start-dev.sh
   ```

4. **Initialize Laravel once (new setup)**

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan key:generate
   docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
   ```

### Access URLs

* App over Traefik (HTTP): [http://webguard.localhost](http://webguard.localhost)
* App over Traefik (HTTPS): [https://webguard.localhost](https://webguard.localhost)
* Vite dev server: [http://localhost:5173](http://localhost:5173)

For frontend HMR in Docker, open the app over HTTP (`http://webguard.localhost`) so Vite assets are loaded from `http://webguard.localhost:5173`.

### Hosts entries

No hosts entry is required for `*.localhost` domains in modern systems.

If you switch to a custom local domain, add a hosts entry like:

```text
127.0.0.1 webguard.test
```

### Common commands

* Run migrations:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml exec php php artisan migrate
  ```

* Run queue worker manually:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml exec queue-default php artisan queue:work redis --once
  ```

* Build frontend assets with Bun:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun run build
  ```

* Run Bun install in the Node container:

  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml run --rm node bun install
  ```

## webguard-instance Integration (Docker Networking)

The Docker stack uses a shared bridge network named `webguard-network`.
For cross-project communication, attach `webguard-instance` to that same network and call:

* Internal WebGuard API base URL: `http://webguard-core/api/v1/internal`
* Auth headers required by WebGuard:
  * `X-INSTANCE-CODE`
  * `X-API-KEY`

Example `docker-compose` snippet in `webguard-instance-v2`:

```yaml
services:
  webguard-instance:
    networks:
      - webguard-network
    environment:
      WEBGUARD_CORE_API_URL: http://webguard-core/api/v1/internal

networks:
  webguard-network:
    external: true
```

The API key must match the configured server instance in WebGuard Admin.

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

3. Run migrations:

   ```bash
   php artisan migrate
   ```

4. Run development processes:

   ```bash
   bun run dev
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
