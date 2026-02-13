#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www"

if [ ! -f "$APP_DIR/composer.json" ]; then
  echo "Initializing Symfony skeleton in $APP_DIR"
  mkdir -p "$APP_DIR"
  cd "$APP_DIR"

  SYMFONY_VERSION="${SYMFONY_VERSION:-6.4.*}"
  composer create-project "symfony/skeleton:${SYMFONY_VERSION}" . --no-interaction --no-progress
  composer require symfony/orm-pack --no-interaction --no-progress
  composer require --dev symfony/maker-bundle --no-interaction --no-progress

  cat > .env.local <<'ENVLOCAL'
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
ENVLOCAL

  php bin/console doctrine:database:create --if-not-exists
  php bin/console cache:clear

  chown -R www-data:www-data var
  touch .initialized
fi

exec "$@"
