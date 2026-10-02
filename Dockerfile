FROM composer:2 AS composer-dependencies

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts

FROM node:24-alpine AS frontend-assets

WORKDIR /app

COPY package.json ./
RUN npm install --no-audit --no-fund

COPY resources ./resources
COPY vite.config.js ./

RUN npm run build

FROM php:8.5-fpm-bookworm AS development

ARG APP_UID=1000
ARG APP_GID=1000

RUN apt-get update \
    && apt-get install --no-install-recommends -y \
        curl \
        git \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        intl \
        mbstring \
        pcntl \
        pdo_pgsql \
        pgsql \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=composer-dependencies --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend-assets --chown=www-data:www-data /app/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-taskflow.ini

RUN groupmod --gid "${APP_GID}" www-data \
    && usermod --uid "${APP_UID}" --gid "${APP_GID}" www-data \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data vendor storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
