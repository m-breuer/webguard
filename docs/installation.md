# Installation and Operations

## Prerequisites

- PHP 8.4 or higher
- Composer
- Bun
- A supported database, such as MySQL or PostgreSQL
- Redis

## Installation

1. Clone the repository.

   ```bash
   git clone https://github.com/m-breuer/webguard.git
   cd webguard
   ```

2. Install PHP dependencies.

   ```bash
   composer install
   ```

3. Install JavaScript dependencies.

   ```bash
   bun install
   ```

4. Create and configure the environment file.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Configure the database and Redis connection details in `.env`.

5. Run migrations.

   ```bash
   php artisan migrate
   ```

6. Build frontend assets.

   ```bash
   bun run build
   ```

7. Start the development environment.

   ```bash
   composer dev
   ```

   The development command starts the Laravel development server, the default queue worker, the dedicated Redis-backed `heartbeat` queue worker, the Pail log viewer, and the Vite development server.

8. Run the test suite.

   ```bash
   php artisan test
   ```

## Heartbeat Queue Worker

In production, run a dedicated worker for the configured heartbeat queue on the standard Redis connection. If `HEARTBEAT_QUEUE` is not set, it defaults to `heartbeat`.

```bash
php artisan queue:work redis --queue="${HEARTBEAT_QUEUE:-heartbeat}" --sleep=3 --tries=3 --max-time=3600
```
