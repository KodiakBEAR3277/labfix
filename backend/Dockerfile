# syntax=docker/dockerfile:1

# ── Stage 1: build the front-end assets (Vite/Inertia) ──────────────────────
FROM node:20-alpine AS assets
WORKDIR /app
COPY . .
RUN npm ci && npm run build

# ── Stage 2: PHP runtime (nginx + php-fpm under supervisor) ─────────────────
FROM php:8.4-fpm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor unzip git ca-certificates \
        libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first, for layer caching (scripts run later, once the app is in).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
COPY --from=assets /app/public/build ./public/build

COPY docker/nginx.conf      /etc/nginx/nginx.conf.custom
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh   /usr/local/bin/entrypoint.sh

# Normalise any CRLF line endings that may have slipped in via the host OS.
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Render injects PORT (defaults to 10000 in the entrypoint).
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
