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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    echo 'upload_max_filesize=8M'; \
    echo 'post_max_size=12M'; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

COPY . .

# Production target: includes compiled frontend assets
FROM php AS production

COPY --from=node-build /app/public/build ./public/build
