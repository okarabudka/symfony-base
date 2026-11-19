#!/bin/sh
# Production entrypoint: warm the cache; the web/app container also runs migrations.
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    # Tables for the Doctrine messenger transport (auto_setup is off).
    php bin/console messenger:setup-transports --no-interaction
fi

php bin/console cache:warmup

# The single-container image starts as root (Nginx); PHP-FPM workers run as www-data and need to write.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data var database
fi

exec "$@"
