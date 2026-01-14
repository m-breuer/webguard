# Stage 1: Frontend asset production
FROM node:24-alpine AS frontend
WORKDIR /app
COPY package.json .
COPY package-lock.json .
COPY tsconfig.json .
COPY vite.config.js .
COPY tailwind.config.js .
COPY postcss.config.js .
COPY resources/ /app/resources
RUN npm install
RUN npm run build

# Stage 2: Backend dependency production
FROM serversideup/php:8.5-frankenphp AS backend
WORKDIR /app
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json .
COPY composer.lock .
RUN composer install --no-dev --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative
RUN php artisan migrate --force --no-interaction
RUN php artisan scribe:generate
RUN php artisan event:cache
RUN php artisan view:cache
RUN php artisan config:cache
RUN php artisan route:cache

# Stage 3: Final image
FROM serversideup/php:8.5-frankenphp
WORKDIR /app
ENV DEBIAN_FRONTEND=noninteractive
ENV DOCKER_CHANNEL=stable
ENV DOCKER_VERSION=5:24.0.7-1~debian.11~bullseye
ENV SUPERCRON_CRON_IN_CONTAINER=false
ENV LARAVEL_OCTANE=false
ENV PHP_OPCACHE_ENABLE=true
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV PHP_OPCACHE_REVALIDATE_FREQ=0
ENV PHP_OPCACHE_JIT=1255
ENV PHP_OPCACHE_JIT_BUFFER_SIZE=256M
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=60

COPY --from=frontend /app/public/build /app/public/build
COPY --from=backend /app /app

RUN sudo cp .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN sudo chown -R webuser:webgroup /app
RUN sudo chmod -R 755 /app/storage
RUN sudo chmod -R 755 /app/bootstrap/cache
RUN sudo find /app -type f -name "*.php" -exec chmod 644 {} \;
RUN sudo chmod -R ug+w /app/storage /app/bootstrap/cache
RUN sudo /usr/local/bin/install-php-extensions apcu bcmath imagick pdo_pgsql redis sockets zip

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
