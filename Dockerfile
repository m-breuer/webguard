############################################
# Base Image
############################################

# Learn more about the Server Side Up PHP Docker Images at:
# https://serversideup.net/open-source/docker-php/
FROM serversideup/php:8.5-fpm-nginx AS base

# Additional PHP extensions
USER root
RUN install-php-extensions bcmath gd intl pdo_mysql zip redis

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
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
USER www-data

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-req=ext-redis
COPY . .
RUN rm -f bootstrap/cache/*.php && \
    composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

FROM base AS production
COPY --from=app_build --chown=www-data:www-data /app /var/www/html
USER www-data
WORKDIR /var/www/html

############################################
# Production Worker Image
############################################
FROM serversideup/php:8.5-cli AS worker
USER root
RUN install-php-extensions redis
# Copy application code from the build stage
COPY --from=app_build --chown=www-data:www-data /app /var/www/html
USER www-data
WORKDIR /var/www/html
