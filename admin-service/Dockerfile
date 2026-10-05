# Node stage: builds Vite assets once Laravel + Breeze are installed
FROM node:22-alpine AS node-build

WORKDIR /app

COPY package.json package-lock.json* ./
RUN if [ -f package.json ]; then npm ci; fi

COPY . .

RUN if [ -f vite.config.js ] || [ -f vite.config.ts ]; then npm run build; fi

# PHP-FPM stage (default build target)
FROM php:8.4-fpm-bookworm AS php

RUN rm -rf /var/lib/apt/lists/* \
    && apt-get update \
    && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Production target: includes compiled frontend assets
FROM php AS production

COPY --from=node-build /app/public/build ./public/build
