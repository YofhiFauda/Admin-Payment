## ═══════════════════════════════════════════════════════════════════
##  Stage 1: PHP Vendor Dependencies
## ═══════════════════════════════════════════════════════════════════
FROM composer:2.9.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./

ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs

## ═══════════════════════════════════════════════════════════════════
##  Stage 2: Frontend Assets (Vite + Tailwind)
## ═══════════════════════════════════════════════════════════════════
FROM node:22-alpine AS node
WORKDIR /app

# Install dependencies terlebih dahulu (layer caching)
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

# Copy source yang dibutuhkan Vite
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

# Teruskan build arguments dari docker-compose.yaml agar Vite bisa embed config real
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

# Copy env example agar Vite punya fallback variabel VITE_REVERB_* saat build jika kosong
COPY .env.example ./.env

RUN npm run build

## ═══════════════════════════════════════════════════════════════════
##  Stage 3: Production PHP-FPM
## ═══════════════════════════════════════════════════════════════════
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libjpeg62-turbo-dev libwebp-dev \
    libfreetype6-dev libonig-dev libxml2-dev libicu-dev libzip-dev \
    zip unzip autoconf gcc make netcat-openbsd procps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy composer binary (untuk dump-autoload)
COPY --from=composer:2.9.7 /usr/bin/composer /usr/bin/composer

# Copy seluruh source code
COPY --chown=www-data:www-data . /var/www

# Copy vendor dari Stage 1
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/vendor

# Copy built node assets dari Stage 2
COPY --from=node --chown=www-data:www-data /app/public/build /var/www/public/build

# ─────────────────────────────────────────────────────────────────────
# Partial build-stage cache — HANYA yang aman (tidak bergantung runtime env)
# ─────────────────────────────────────────────────────────────────────

# Optimasi autoloader (--no-scripts prevents artisan calls that need a full .env at build time)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer dump-autoload --optimize --no-dev --no-scripts

# \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
# \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
# Partial build-stage cache \u2014 HANYA yang aman (tidak bergantung runtime env)
#
# \u2705 BOLEH di build stage:
#   - package:discover : discover service providers (struktur, bukan nilai env)
#   - view:cache       : compile Blade templates (tidak ada env dependency)
#   - event:cache      : discover listeners/subscribers (struktur kode)
#
# \u274c TIDAK BOLEH di build stage:
#   - config:cache  : bake SEMUA env() jadi nilai FINAL dari .env.example
#                     \u2192 runtime ENV docker-compose akan DIABAIKAN oleh Laravel
#   - route:cache   : dilakukan di runtime (entrypoint.sh) setelah config ready
#
# config:cache & route:cache dilakukan di entrypoint.sh (APP role)
# menggunakan env var container aktual, bukan nilai .env.example.
# \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
# Artisan optimasi dan vendor:publish telah dipindahkan ke entrypoint.sh
# untuk menghindari error koneksi / timeout saat proses docker build.

# ⚠️ WORKAROUND UNTUK DOCKER VOLUME
# Simpan copy dari public directory agar bisa di-sync ke shared volume saat boot.
# Dilakukan setelah vendor:publish agar assets dashboard ikut tersalin.
RUN cp -a /var/www/public /var/www/public_source

# Copy konfigurasi PHP & PHP-FPM production
COPY docker/php/production.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php-fpm/production.conf /usr/local/etc/php-fpm.d/zz-production.conf

# Siapkan direktori storage & permissions
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]