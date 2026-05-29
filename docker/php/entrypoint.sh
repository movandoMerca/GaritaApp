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

if [ -f .env ]; then
    sed -i 's/\r$//' .env
fi

rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/services.php bootstrap/cache/packages.php

if [ -n "$DB_HOST" ]; then
    until mysqladmin --ssl=0 ping -h"$DB_HOST" -P"${DB_PORT:-3306}" --silent; do
        echo "Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
        sleep 2
    done
fi

if [ -n "$MYSQL_ROOT_PASSWORD" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ] && [ "$DB_USERNAME" != "root" ]; then
    mysql --ssl=0 -h"$DB_HOST" -P"${DB_PORT:-3306}" -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
        CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`;
        CREATE USER IF NOT EXISTS '$DB_USERNAME'@'%' IDENTIFIED BY '$DB_PASSWORD';
        ALTER USER '$DB_USERNAME'@'%' IDENTIFIED BY '$DB_PASSWORD';
        GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'%';
        FLUSH PRIVILEGES;
EOSQL
fi

if [ -z "$APP_KEY" ] && [ -f .env ]; then
    APP_KEY="$(grep -E '^APP_KEY=' .env | tail -n 1 | cut -d '=' -f2- | tr -d '\r')"
    export APP_KEY
fi

if [ -z "$APP_KEY" ]; then
    GENERATED_APP_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
    export APP_KEY="$GENERATED_APP_KEY"

    if grep -q '^APP_KEY=' .env; then
        sed -i "s#^APP_KEY=.*#APP_KEY=$GENERATED_APP_KEY#" .env
    else
        printf '\nAPP_KEY=%s\n' "$GENERATED_APP_KEY" >> .env
    fi
fi

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY could not be generated." >&2
    exit 1
fi

echo "APP_KEY is configured."

php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
