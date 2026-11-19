#!/bin/sh
# Runs PHP-FPM in the background and Nginx in the foreground; the container stops when Nginx stops.
set -e

php-fpm --daemonize
exec nginx -g 'daemon off;'
