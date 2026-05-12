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
FROM node:20-alpine AS frontend
WORKDIR /app

# Install dependencies terlebih dahulu (layer caching)
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

# Copy source yang dibutuhkan Vite
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

## ═══════════════════════════════════════════════════════════════════
##  Stage 3: Production PHP-FPM
## ═══════════════════════════════════════════════════════════════════
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libjpeg62-turbo-dev libwebp-dev \
    libfreetype6-dev libonig-dev libxml2-dev libicu-dev libzip-dev \
    zip unzip autoconf gcc make \
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

# Copy built frontend assets dari Stage 2
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/public/build

# ⚠️ WORKAROUND UNTUK DOCKER VOLUME
# Simpan copy dari public directory agar bisa di-sync ke shared volume saat boot
RUN cp -a /var/www/public /var/www/public_source

# Optimasi autoloader
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

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