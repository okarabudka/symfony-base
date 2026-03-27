#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www"

if [ ! -f "$APP_DIR/.initialized" ]; then
  echo "==> Initializing Symfony skeleton in $APP_DIR"
  mkdir -p "$APP_DIR"
  cd "$APP_DIR"

  # Empty SYMFONY_VERSION means latest stable
  if [ -n "${SYMFONY_VERSION:-}" ]; then
    PACKAGE="symfony/skeleton:${SYMFONY_VERSION}"
  else
    PACKAGE="symfony/skeleton"
  fi

  composer create-project "$PACKAGE" . --no-interaction --no-progress

  composer require symfony/orm-pack --no-interaction --no-progress
  composer require --dev symfony/maker-bundle symfony/test-pack phpstan/phpstan --no-interaction --no-progress

  cat > .env.local <<'ENVLOCAL'
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
ENVLOCAL

  php bin/console doctrine:database:create --if-not-exists
  php bin/console cache:clear

  chown -R www-data:www-data var

  touch /var/www/.initialized
  echo "==> Symfony initialized successfully"
fi

exec "$@"
