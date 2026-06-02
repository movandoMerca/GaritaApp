FROM php:8.2-fpm-alpine AS app

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
        bash \
        freetype-dev \
        git \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        mariadb-client \
        oniguruma-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        dom \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip

WORKDIR /var/www/html

COPY composer.json composer.lock /tmp/garita-composer/
RUN cd /tmp/garita-composer \
    && composer install --prefer-dist --no-interaction --no-progress --no-scripts \
    && mkdir -p /opt/garita \
    && cp -a vendor /opt/garita/vendor \
    && rm -rf /tmp/garita-composer

COPY docker/php/entrypoint.sh /usr/local/bin/garita-entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/garita-entrypoint \
    && chmod +x /usr/local/bin/garita-entrypoint

ENTRYPOINT ["/usr/local/bin/garita-entrypoint"]
CMD ["php-fpm"]
