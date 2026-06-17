# syntax=docker/dockerfile:1.7

############################################
# Base Image
############################################

# Learn more about the Server Side Up PHP Docker Images at:
# https://serversideup.net/open-source/docker-php/
FROM serversideup/php:8.5-fpm-nginx AS base

# Additional PHP extensions
USER root
RUN install-php-extensions bcmath gd intl pdo_mysql sockets zip redis

############################################
# Development Image
############################################
FROM base AS development

# We can pass USER_ID and GROUP_ID as build arguments
# to ensure the www-data user has the same UID and GID
# as the user running Docker.
ARG USER_ID
ARG GROUP_ID

# Switch to root so we can set the user ID and group ID
USER root
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID
USER www-data

############################################
# CI image
############################################
FROM base AS ci

# Sometimes CI images need to run as root
USER root

############################################
# Production Image
############################################
FROM base AS app_build
# Install Composer
USER root
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
RUN chown www-data:www-data /app
USER www-data
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --link --chown=33:33 composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked,uid=33,gid=33 \
    composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --no-progress --prefer-dist --ignore-platform-req=ext-redis
COPY --link --chown=33:33 . .
RUN rm -f bootstrap/cache/*.php && \
    composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

FROM oven/bun:1 AS frontend_build
WORKDIR /app
ENV BUN_INSTALL_CACHE_DIR=/tmp/bun-cache
COPY --link package.json bun.lock ./
RUN --mount=type=cache,target=/tmp/bun-cache,sharing=locked \
    bun install --frozen-lockfile
COPY --link resources resources
COPY --link public public
COPY --link postcss.config.js tailwind.config.js tsconfig.json vite.config.js ./
RUN bun run build

FROM base AS production
COPY --link --from=app_build --chown=33:33 /app /var/www/html
COPY --link --from=frontend_build --chown=33:33 /app/public/build /var/www/html/public/build
COPY --link docker/php/entrypoint.d/ /etc/entrypoint.d/
RUN chmod +x /etc/entrypoint.d/*.sh
USER www-data
WORKDIR /var/www/html

############################################
# Production Worker Image
############################################
FROM serversideup/php:8.5-cli AS worker
USER root
RUN install-php-extensions redis
# Copy application code from the build stage
COPY --link --from=app_build --chown=33:33 /app /var/www/html
USER www-data
WORKDIR /var/www/html
