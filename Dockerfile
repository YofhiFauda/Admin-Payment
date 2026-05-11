## ═══════════════════════════════════════════════════════════════════
##  Stage 1: Download Dependencies (Tanpa Scripts)
## ═══════════════════════════════════════════════════════════════════
FROM composer:2.9.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./

ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1

# TAMBAHKAN --ignore-platform-reqs untuk bypass platform checks
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs

## ═══════════════════════════════════════════════════════════════════
##  Stage 2: Production PHP-FPM (TETAP SAMA)
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

COPY --from=composer:2.9.7 /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data . /var/www
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/vendor

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]