# Targets: dev (local, code mounted), app (production PHP-FPM), web (production Nginx with compiled assets),
# server (Nginx + PHP-FPM in one container, for Kamal).

FROM php:8.5-fpm-alpine AS base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_sqlite pdo_mysql pdo_pgsql intl zip opcache redis pcntl

WORKDIR /var/www


FROM base AS dev

# Code coverage for `make coverage` and `make mutate`.
RUN install-php-extensions pcov

# Match the host user so files written to the mounted project stay editable.
ARG UID=1000
ARG GID=1000
RUN addgroup -g "${GID}" app && adduser -D -u "${UID}" -G app app

USER app


FROM base AS app

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY docker/php/conf.d/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY --chown=www-data:www-data . .

# The secret is only needed to boot the kernel at build time; the real one comes from the environment.
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts \
    && APP_SECRET=build php bin/console importmap:install \
    && APP_SECRET=build php bin/console asset-map:compile \
    && mkdir -p var/share database \
    && chown -R www-data:www-data var database public

USER www-data

COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD pgrep -f "php-fpm: master" > /dev/null || exit 1


FROM nginx:1.29-alpine AS web

COPY docker/conf/nginx/production.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/public /var/www/public

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO /dev/null http://127.0.0.1/health || exit 1


FROM app AS server

USER root

RUN apk add --no-cache nginx \
    && rm -f /etc/nginx/http.d/default.conf

COPY docker/conf/nginx/production.conf /etc/nginx/http.d/app.conf
RUN sed -i 's#fastcgi_pass app:9000;#fastcgi_pass 127.0.0.1:9000;#' /etc/nginx/http.d/app.conf \
    && grep -q 'fastcgi_pass 127.0.0.1:9000;' /etc/nginx/http.d/app.conf

COPY --chmod=755 docker/php/server.sh /usr/local/bin/server
CMD ["server"]

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO /dev/null http://127.0.0.1/health || exit 1
