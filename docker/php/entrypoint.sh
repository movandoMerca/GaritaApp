#!/bin/sh
set -e

mkdir -p \
    storage/app/public \
    storage/app/visits \
    storage/configuracion/datos \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ -n "$DB_HOST" ]; then
    until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" --silent; do
        echo "Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
        sleep 2
    done
fi

if [ -z "$APP_KEY" ]; then
    GENERATED_APP_KEY="$(php artisan key:generate --show --no-interaction)"
    export APP_KEY="$GENERATED_APP_KEY"

    if grep -q '^APP_KEY=' .env; then
        sed -i "s#^APP_KEY=.*#APP_KEY=$GENERATED_APP_KEY#" .env
    else
        printf '\nAPP_KEY=%s\n' "$GENERATED_APP_KEY" >> .env
    fi
fi

php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
