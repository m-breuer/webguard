# WebGuard

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> 💡 **System Architecture Note:** This repository contains the **Management Core & API**. For the distributed scanning node/worker, please visit the [WebGuard Instance Repository](https://github.com/m-breuer/webguard-instance-v2).

WebGuard is a powerful, open-source web monitoring service built with Laravel 13. It's designed to help you track website uptime, response times, and SSL certificate statuses with ease. Whether you're a developer, a small business owner, or a system administrator, WebGuard provides the tools you need to ensure your online services are running smoothly.

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

The standard deployment stack contains:

* `php`
* `schedule`
* `queue-default`
* `mysql`
* `redis`

Use `.env.example` as the starting point for `.env`.
Minimum `.env` fields for production:

```env
APP_NAME=WebGuard
APP_ENV=production
APP_DEBUG=false
APP_URL=https://webguard.example.com
APP_KEY=base64:...
APP_TIMEZONE=Europe/Berlin
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=webguard_core
DB_USERNAME=webguard
DB_PASSWORD=super-secret-password
DB_ROOT_PASSWORD=super-secret-root-password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=mailer-user
MAIL_PASSWORD=mailer-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=WebGuard

WEBGUARD_CORE_INTERNAL_API_URL=https://webguard.example.com/api/v1/internal
WEBGUARD_INSTANCE_CODE=...
WEBGUARD_INSTANCE_API_KEY=...
```

Optional:
* `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` for GitHub login
* `IMPRINT_*` fields for legal/imprint content

The application listens internally on port `8080`.
If you use Traefik or another reverse proxy in front of the deployment, route traffic to the `php` service on that port.

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
DB_ROOT_PASSWORD=root

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@webguard.test
MAIL_FROM_NAME=WebGuard

VITE_DEV_SERVER_URL=http://webguard.test:5173
VITE_HMR_HOST=webguard.test

WEBGUARD_INSTANCE_CODE=
WEBGUARD_INSTANCE_API_KEY=your_generated_key_here
WEBGUARD_CORE_INTERNAL_API_URL=http://webguard-core/api/v1/internal

DOCKER_APP_HOST=webguard.test
DOCKER_MAILPIT_HOST=mailpit.webguard.test
DOCKER_HTTP_PORT=80
DOCKER_HTTPS_PORT=443
DOCKER_VITE_PORT=5173
DOCKER_MYSQL_PORT=3306
DOCKER_MAILPIT_SMTP_PORT=1025
DOCKER_MAILPIT_UI_PORT=8025
DOCKER_NETWORK_NAME=webguard-network
QUEUE_WORKER_QUEUE=default
QUEUE_WORKER_SLEEP=1
QUEUE_WORKER_TRIES=1
QUEUE_WORKER_MAX_TIME=3600
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
