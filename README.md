# symfony-skeleton-bootstrap

Infrastructure-only repository that bootstraps a fresh Symfony skeleton on first run.

## Run project (dev)

```bash
make up-containers
```

Optional services via profiles:

```bash
docker compose --profile mail up -d
docker compose --profile cache up -d
docker compose --profile node up -d
docker compose --profile mysql up -d
```

## Check availability

- http://localhost:8080 → Symfony.
- http://localhost:8025 → MailHog (only with `mail` profile).

## What happens on first start

When the `app` container starts and `/var/www` is empty, it automatically runs:

- `composer create-project symfony/skeleton`
- installs `symfony/orm-pack` and `symfony/maker-bundle`
- sets SQLite as the default DB in `.env.local`
- creates the SQLite database

The Symfony project is stored inside the `app_data` Docker volume (not in git).

## Compose variables

You can override ports and service settings via environment variables. Useful variables:

- `APP_PORT`
- `SYMFONY_VERSION` (default `6.4.*`)
- `MYSQL_DATABASE` (only with `mysql` profile)
- `MYSQL_USER` (only with `mysql` profile)
- `MYSQL_PASSWORD` (only with `mysql` profile)
- `MYSQL_ROOT_PASSWORD` (only with `mysql` profile)
- `MYSQL_PORT` (only with `mysql` profile)
- `REDIS_PORT`
- `MAILHOG_HTTP_PORT`
- `MAILHOG_SMTP_PORT`

## PHP settings

Dev overrides: `docker/php/conf.d/dev` (mounted in dev).

## Checks

```bash
make lint
make phpstan
make test
```

## Notes

- This template intentionally does not include Symfony sources in the repo.
- To export the generated app into the repo, use `make export-app` (copies to `./app`).  
  Remove `/app/` from `.gitignore` if you want to commit it.
