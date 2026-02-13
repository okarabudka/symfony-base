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

COPY docker/entrypoint/init-symfony.sh /usr/local/bin/init-symfony

ENTRYPOINT ["init-symfony"]
CMD ["php-fpm"]
