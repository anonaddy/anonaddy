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
ENV COMPOSER_NO_INTERACTION=1

FROM php-base AS composer-validate
RUN --mount=type=bind,target=.,ro \
  --mount=type=cache,target=/tmp/cache/composer <<EOT
  set -ex
  composer validate --strict --no-check-publish --check-lock
  composer --working-dir=postfix validate --no-check-publish --check-lock
EOT

FROM node:${NODE_VERSION}-alpine AS npm-base
WORKDIR /src
ENV NPM_CONFIG_AUDIT=false
ENV NPM_CONFIG_FUND=false

FROM npm-base AS npm-validate
RUN --mount=type=bind,target=.,rw \
  --mount=type=cache,target=/root/.npm \
  --mount=type=cache,target=/src/node_modules \
  npm ci --ignore-scripts
