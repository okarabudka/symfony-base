# symfony-skeleton

Symfony 7.2 starter template with PHP 8.4, MySQL 8.4, Docker, PHPStan, PHPUnit and GitHub Actions CI.

## Quick start (dev)

```bash
cp .env .env.local        # set APP_SECRET and adjust credentials if needed
make up-containers
make composer-install
make migrate
```

## URLs

- http://localhost:8080 → Symfony
- http://localhost:8025 → Mailpit web UI (only with `mail` profile)

## Optional services (via profiles)

```bash
docker compose --profile mail up -d    # Mailpit (SMTP testing)
docker compose --profile cache up -d   # Redis
docker compose --profile node up -d    # Node.js
```

## Production build

Create `.env.local` with real values **before** building — these are baked into the image:

```bash
# .env.local (not committed)
APP_SECRET=<random 32+ char string>
MYSQL_USER=myapp
MYSQL_PASSWORD=<strong password>
MYSQL_ROOT_PASSWORD=<strong password>
MYSQL_DATABASE=myapp
```

Then build and start:

```bash
make up-prod
```

The prod image is self-contained: static assets and compiled cache are baked in during build.
`APP_SECRET` and `DATABASE_URL` are required at runtime — Docker will fail to start if they are unset.

## PHP settings

Dev overrides: `docker/php/conf.d/dev` (mounted in dev).
Prod overrides: `docker/php/conf.d/prod` (baked into prod image).

## Available make commands

```bash
make help
```

## Code quality

```bash
make lint       # PHP syntax check
make phpstan    # static analysis
make test       # PHPUnit
```

## Environment variables

Override via `.env.local` (not committed to git):

| Variable              | Default      | Description                          |
|-----------------------|--------------|--------------------------------------|
| `APP_SECRET`          | `REPLACE_ME` | **Must be changed** — random string  |
| `APP_PORT`            | `8080`       | HTTP port exposed by nginx           |
| `MYSQL_DATABASE`      | `app`        | Database name                        |
| `MYSQL_USER`          | `symfony`    | Database user                        |
| `MYSQL_PASSWORD`      | `secret`     | Database password                    |
| `MYSQL_ROOT_PASSWORD` | `root`       | MySQL root password                  |
| `MYSQL_PORT`          | `3306`       | MySQL port (for local tools)         |
| `REDIS_PORT`          | `6379`       | Redis port                           |
| `MAILPIT_HTTP_PORT`   | `8025`       | Mailpit web UI port                  |
| `MAILPIT_SMTP_PORT`   | `1025`       | Mailpit SMTP port                    |
| `ADMIN_EMAIL`         | —            | Admin email for Notifier alerts      |

## CI

GitHub Actions runs on every push and pull request:

- PHP lint
- PHPUnit
- PHPStan
