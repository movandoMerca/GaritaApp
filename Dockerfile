FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources ./resources
COPY webpack.mix.js ./
RUN mkdir -p public \
    && npm run production \
    && if [ ! -f public/mix-manifest.json ]; then printf '{}\n' > public/mix-manifest.json; fi

FROM composer:2.8 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        mariadb-client \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd opcache pdo_mysql zip

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=composer --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/css ./public/css
COPY --from=assets --chown=www-data:www-data /app/public/js ./public/js
COPY --from=assets --chown=www-data:www-data /app/public/mix-manifest.json ./public/mix-manifest.json
COPY docker/php/entrypoint.sh /usr/local/bin/garita-entrypoint

RUN chmod +x /usr/local/bin/garita-entrypoint \
    && mkdir -p storage/app/public storage/app/visits storage/configuracion/datos storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["garita-entrypoint"]
CMD ["php-fpm"]
