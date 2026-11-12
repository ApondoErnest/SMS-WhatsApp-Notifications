# Visite Notify — multi-stage production image
# Targets: app-production, nginx-production

# -----------------------------------------------------------------------------
# Frontend assets
# -----------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# -----------------------------------------------------------------------------
# Composer vendor
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction

# -----------------------------------------------------------------------------
# PHP-FPM application
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-bookworm AS app-production

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
        bcmath \
        opcache \
        posix \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-visite-notify.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-visite-notify.conf
COPY docker/php/entrypoint.sh /usr/local/bin/visite-notify-entrypoint
RUN chmod +x /usr/local/bin/visite-notify-entrypoint

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwx storage bootstrap/cache

ENTRYPOINT ["visite-notify-entrypoint"]
EXPOSE 9000
CMD ["php-fpm", "-F"]

# -----------------------------------------------------------------------------
# Nginx (serves public/ and proxies PHP to app:9000)
# -----------------------------------------------------------------------------
FROM nginx:stable-alpine AS nginx-production

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor /app/public /var/www/html/public

WORKDIR /var/www/html
