FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip git libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev libxml2-dev libcurl4-openssl-dev \
    libicu-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd intl opcache zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

WORKDIR /var/www

COPY docker-app/ /docker-app/
COPY docker/entrypoint/init-symfony.sh /usr/local/bin/init-symfony
RUN chmod +x /usr/local/bin/init-symfony

ENTRYPOINT ["init-symfony"]
CMD ["php-fpm"]
