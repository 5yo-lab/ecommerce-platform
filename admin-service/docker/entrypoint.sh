#!/bin/sh
set -e

php artisan optimize:clear >/dev/null 2>&1 || true

exec docker-php-entrypoint php-fpm
