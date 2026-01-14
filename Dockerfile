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
FROM serversideup/php:8.5-fpm-nginx AS backend
WORKDIR /app
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json .
COPY composer.lock .
RUN composer install --no-dev --optimize-autoloader
COPY . .
RUN php artisan event:cache
RUN php artisan view:cache
RUN php artisan config:cache
RUN php artisan route:cache

# Stage 3: Final image
FROM serversideup/php:8.5-fpm-nginx
WORKDIR /app
ENV DEBIAN_FRONTEND=noninteractive
ENV PHP_UPLOAD_MAX_FILE_SIZE="250M"
ENV PHP_OPCACHE_ENABLE="1"
ENV AUTORUN_ENABLED="true"

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
