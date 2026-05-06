# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4
ARG COMPOSER_VERSION=2
ARG NODE_VERSION=24

FROM composer:${COMPOSER_VERSION} AS composer-bin

FROM php:${PHP_VERSION}-cli-alpine AS php-base
COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
WORKDIR /src
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_CACHE_DIR=/tmp/cache/composer
ENV COMPOSER_NO_AUDIT=1
ENV COMPOSER_NO_INTERACTION=1

FROM php-base AS composer-validate
RUN --mount=type=bind,target=.,ro \
  --mount=type=cache,target=/tmp/cache/composer <<EOT
  set -ex
  composer validate --strict --no-check-publish --check-lock
  composer --working-dir=postfix validate --no-check-publish --check-lock
EOT

FROM php-base AS php-test-base
RUN apk add --no-cache \
    freetype \
    git \
    gnupg \
    gpgme \
    libjpeg-turbo \
    libpng \
    libzip \
    oniguruma \
    redis \
    sqlite-libs \
    unzip \
  && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    freetype-dev \
    gpgme-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd mbstring pdo_sqlite zip \
  && pecl install gnupg mailparse redis \
  && docker-php-ext-enable gnupg mailparse redis \
  && echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/phpunit-memory.ini \
  && apk del .build-deps

FROM php-test-base AS phpunit
RUN --mount=type=bind,target=.,rw \
  --mount=type=cache,target=/tmp/cache/composer \
  --mount=type=cache,target=/src/vendor <<EOT
  set -ex
  export APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
  export APP_ENV=testing
  export DB_CONNECTION=sqlite
  export DB_DATABASE=':memory:'
  [ -f .env ] || cp .env.example .env
  mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
  redis-server --daemonize yes
  composer install --prefer-dist --no-progress --no-scripts
  php artisan package:discover --ansi
  php artisan test
EOT

FROM php-test-base AS composer-lock-generator
WORKDIR /work
COPY composer.json composer.lock ./
COPY postfix/composer.json postfix/composer.lock ./postfix/
RUN --mount=type=cache,target=/tmp/cache/composer <<EOT
  set -ex
  composer update --no-install --no-scripts --no-progress --minimal-changes
  composer --working-dir=postfix update --no-install --no-scripts --no-progress --minimal-changes
  mkdir -p /out/postfix
  cp composer.lock /out/composer.lock
  cp postfix/composer.lock /out/postfix/composer.lock
EOT

FROM scratch AS composer-lock
COPY --from=composer-lock-generator /out/composer.lock /composer.lock
COPY --from=composer-lock-generator /out/postfix/composer.lock /postfix/composer.lock

FROM node:${NODE_VERSION}-alpine AS npm-base
WORKDIR /src
ENV NPM_CONFIG_AUDIT=false
ENV NPM_CONFIG_FUND=false

FROM npm-base AS npm-validate
RUN --mount=type=bind,target=.,rw \
  --mount=type=cache,target=/root/.npm \
  --mount=type=cache,target=/src/node_modules \
  npm ci --ignore-scripts

FROM npm-base AS npm-lock-generator
WORKDIR /work
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm <<EOT
  set -ex
  npm install --package-lock-only --ignore-scripts
  mkdir -p /out
  cp package-lock.json /out/package-lock.json
EOT

FROM scratch AS npm-lock
COPY --from=npm-lock-generator /out/package-lock.json /package-lock.json
