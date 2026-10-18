#!/bin/sh
set -eu
[ "$APP_ENV" = testing ] && [ "$DB_DATABASE" = garita_visits_test ]
cmp -s .env tests/docker/testing.env
mkdir -p storage/app/public storage/app/visits storage/configuracion/datos storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache vendor
if [ ! -f vendor/autoload.php ]; then cp -a /opt/garita/vendor/. vendor/; fi
chown -R www-data:www-data storage bootstrap/cache
exec php-fpm
