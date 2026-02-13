FROM php:8.2-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip git curl libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev libxml2-dev libcurl4-openssl-dev \
    libicu-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd intl opcache \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

FROM base AS dev

CMD ["php-fpm"]

FROM base AS prod

ENV APP_ENV=prod

COPY . /var/www
COPY docker/php/conf.d/prod/ /usr/local/etc/php/conf.d/

RUN composer install --no-dev --classmap-authoritative --no-interaction --no-progress \
    && composer dump-env prod \
    && php bin/console cache:clear --no-warmup \
    && php bin/console cache:warmup \
    && chown -R www-data:www-data var

CMD ["php-fpm"]
