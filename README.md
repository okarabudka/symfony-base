# symfony-base

An infrastructure-only repository that creates a fresh Symfony project inside Docker on first start and can then hand it over as a plain Symfony repository.

## Context

A personal starter template by Oleksandr Karabudka. It started in May 2025 as a Symfony project with a Docker setup; in February and March 2026 it was reworked into a bootstrap repository: the Symfony application files were removed from git, a production and development Docker setup with a CI template was added, and a `finalize` command was introduced. The goal is to start every new Symfony project from the current stable version with the same Docker environment, Doctrine, testing and static analysis already in place.

## Features

- First start runs `composer create-project symfony/skeleton` inside the `app` container (latest stable, or a pinned version via `SYMFONY_VERSION`).
- Automatic setup after install: `symfony/orm-pack`, and as dev dependencies `symfony/maker-bundle`, `symfony/test-pack` and `phpstan/phpstan`; SQLite configured as the default database in `.env.local`; cache cleared.
- Health check on an `.initialized` marker; nginx starts only after the app is ready.
- Optional services through Docker Compose profiles: MySQL 8.4, Mailpit, Redis, Node.js 22.
- Makefile commands for Composer, Doctrine migrations, PHPUnit, syntax check and PHPStan.
- `make export-app` copies the generated app to `./app/` for review.
- `make finalize` (one-way, asks for confirmation) replaces the repository content with the generated app, which then contains its own `Dockerfile`, `docker-compose.yml` and GitHub Actions CI.

## Tech stack

- PHP 8.4 (FPM) with pdo_mysql, gd, intl, opcache, zip; Composer 2
- Symfony (latest stable by default), Doctrine ORM, MakerBundle, PHPUnit, PHPStan
- nginx, SQLite (default), optional MySQL 8.4, Redis, Mailpit, Node.js 22
- Docker Compose, Makefile, Bash
- GitHub Actions (CI template for the generated app)

## Architecture

- `Dockerfile` builds the PHP image with dev and OPcache settings baked in; its entrypoint `docker/entrypoint/init-symfony.sh` bootstraps Symfony into the `app_data` named volume on first run and then starts PHP-FPM.
- `docker/nginx` builds the web server image that serves the same volume.
- `docker-app/` holds the files that are copied into the generated app: a standalone `Dockerfile`, `docker-compose.yml`, nginx config and `.github/workflows/ci.yml` (composer validate, lint, PHPStan, PHPUnit).
- `scripts/finalize.sh` exports the app from the volume with host file ownership, checks that `composer.json` exists, removes all bootstrap files and copies the app to the repository root.

## Getting started

Requires Docker, Docker Compose and make.

```bash
make up-containers   # build and start; first run installs Symfony (1 to 3 minutes)
make logs            # follow progress
```

Open http://localhost:8080 (port configurable with `APP_PORT`). With the `mail` profile, Mailpit is at http://localhost:8025.

Pin a Symfony version before the first run (or after `make reset`), either on the command line or in `.env.local`:

```bash
SYMFONY_VERSION=7.2.* make up-containers
```

Optional services:

```bash
docker compose --profile mysql up -d   # MySQL 8.4
docker compose --profile mail up -d    # Mailpit
docker compose --profile cache up -d   # Redis
docker compose --profile node up -d    # Node.js 22
```

Switch from SQLite to MySQL:

```bash
docker compose --profile mysql up -d
make bash
# inside the container:
echo 'DATABASE_URL="mysql://symfony:secret@mysql:3306/app?serverVersion=8.4"' >> .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
```

| Variable | Default | Description |
|---|---|---|
| `SYMFONY_VERSION` | latest | Symfony version constraint, e.g. `7.2.*` |
| `APP_PORT` | `8080` | HTTP port |
| `MYSQL_DATABASE` | `app` | MySQL database (profile `mysql`) |
| `MYSQL_USER` | `symfony` | MySQL user (profile `mysql`) |
| `MYSQL_PASSWORD` | `secret` | MySQL password (profile `mysql`) |
| `MYSQL_ROOT_PASSWORD` | `root` | MySQL root password (profile `mysql`) |
| `MYSQL_PORT` | `3306` | MySQL host port (profile `mysql`) |
| `REDIS_PORT` | `6379` | Redis host port (profile `cache`) |
| `MAILPIT_HTTP_PORT` | `8025` | Mailpit web UI port (profile `mail`) |
| `MAILPIT_SMTP_PORT` | `1025` | Mailpit SMTP port (profile `mail`) |

Other commands: `make down`, `make reset` (delete the volume and bootstrap again), `make bash`, `make composer ARGS="require foo"`, `make migrate`, `make help`.

When ready to build the actual project:

```bash
make export-app   # preview the app in ./app/
make finalize     # one-way: replace the repository with the generated app
```

## Testing

The repository itself has no tests. For the generated app:

```bash
make test      # php bin/phpunit
make lint      # php -l on src/ and tests/
make phpstan   # PHPStan
```

After `finalize`, the same checks run in GitHub Actions.

## Status

Personal starter template (2025 to 2026), last changed in March 2026.

## License

MIT, see [LICENSE](LICENSE).
