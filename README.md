# symfony-media-app

Simple Symfony 6.4 Media application

## Run project (dev)

```bash
make up-containers
make composer-install
```

Optional services via profiles:

```bash
docker compose --profile mail up -d
docker compose --profile cache up -d
docker compose --profile node up -d
```

## Check availability

- http://localhost:8080 → Symfony.
- http://localhost:8025 → MailHog (only with `mail` profile).

## Run project (prod build)

```bash
make up-prod
```

Defaults can be overridden via `.env.local` (not committed). Useful variables:

- `APP_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_PORT`
- `REDIS_PORT`
- `MAILHOG_HTTP_PORT`
- `MAILHOG_SMTP_PORT`

## PHP settings

Dev overrides: `docker/php/conf.d/dev` (mounted in dev).  
Prod overrides: `docker/php/conf.d/prod` (baked into prod image).

## Checks

```bash
make lint
make phpstan
make test
```

## CI

GitHub Actions runs:

- PHP lint
- PHPUnit
- PHPStan
