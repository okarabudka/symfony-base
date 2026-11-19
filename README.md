<!-- symfony-base template readme -->
# symfony-base

[![CI](https://github.com/okarabudka/symfony-base/actions/workflows/ci.yml/badge.svg)](https://github.com/okarabudka/symfony-base/actions/workflows/ci.yml)

A starter kit for new Symfony projects that always starts from the newest Symfony release. The repository holds no
Symfony code. It holds the setup every new project needs before its first feature: a Docker environment, quality
checks, CI and a production build. On the first `make up` it installs the latest Symfony and applies that setup on
top. It is the Symfony counterpart of [laravel-base](https://github.com/okarabudka/laravel-base) and uses the same
commands.

## Why it exists

Every new Symfony project starts with the same work: a Docker setup that does not fight file permissions, the right
set of packs, PHPStan and a code style tool, CI, sensible defaults, and eventually a way to run it in production.
Each project ends up solving this a little differently.

symfony-base makes those decisions once. A template with a committed skeleton falls behind the framework. This one
installs the current Symfony release every time you start a project, so you never begin on an outdated version.

## How it works

1. `make up` runs `bin/generate` in the PHP container. It creates a project from the newest `symfony/skeleton` in a
   temporary directory and installs the packs listed below; Flex recipes configure them.
2. The template is applied to that fresh project:
   - `overlay/` adds the template's own files: the start page, the health check, request ids, the production command
     guard, the tests and the tool configuration;
   - `bin/patch-skeleton.php` makes small, anchored edits to recipe files: SQLite and Mailpit in `.env`, no secret in
     `.env.dev`, Composer scripts for the make targets.
3. The finished project moves into the repository root. If any step fails, the root is left untouched.
4. Every later `make up` just starts the containers.

In this repository the generated files are git-ignored. `make init` turns a copy into your own project. From then on
the generated code is ordinary project code that you commit.

CI generates a fresh project on every push and once a week. If a new Symfony release changes a recipe the template
relies on, a patch anchor stops generation and CI turns red, instead of producing a half-configured project.

To pin a version: `SYMFONY_VERSION=8.1.* make up`.

## What you get

**A working project in minutes.** The latest Symfony on PHP 8.5 with Twig, Doctrine ORM and migrations,
AssetMapper (no Node build), Mailer, Messenger, Scheduler and Monolog. SQLite by default. `make up` creates the TLS
certificate and a generated `APP_SECRET` in `.env.local`, builds the containers, installs dependencies and runs
migrations. You only need Docker, make and openssl on your machine.

**A local environment close to production.** Nginx with HTTPS, PHP-FPM and Mailpit to catch outgoing mail.
MariaDB and Redis are one profile away. Containers run as your user, so files created inside them stay editable on
the host.

**Observability from day one.** In production, logs go to stderr as JSON. Every request gets an `X-Request-Id` that
appears in all of its log entries. `/health` checks the database, cache and message queue and returns 503 when one of
them fails.

**Quality checks from the first commit.**
- PHPUnit; deprecations, notices and warnings fail the suite.
- PHP CS Fixer with the Symfony rule set.
- PHPStan at the strictest level with the Symfony, Doctrine and PHPUnit extensions, tests included.
- `lint:container`, `lint:yaml`, `lint:twig` and Doctrine mapping validation.
- Code coverage with an 80 % minimum and mutation testing with Infection (70 % covered MSI).

**Strict defaults.** Twig fails on unknown variables in every environment. `doctrine:database:drop`,
`doctrine:schema:drop` and `doctrine:fixtures:load` are blocked in production. Tests cover each of these.

**A path to production.** The Dockerfile builds a production PHP-FPM image without dev dependencies, with an
authoritative class map, OPcache and compiled assets, an Nginx image for the public files, and a single-container
image for Kamal. `compose.prod.yaml` runs them with a Messenger worker and the scheduler, with health checks on every
service.

## How to use it

### Start a new project

Click **Use this template** on GitHub (or clone this repository), then:

```bash
git clone <your-repository-url> my-app && cd my-app
make init NAME=my-app
make up
git add -A && git commit -m "Start my-app from symfony-base"
```

`make init` generates the project if needed and renames it:
- sets `APP_NAME`;
- sets the Composer name (`<github-owner>/my-app`);
- sets the service and image names in the Kamal configuration;
- replaces this README with a short project README that carries your CI badge;
- removes the template's git-ignore block, so the generated code becomes part of your project.

Optional modules can be added at the same time or later:

```bash
make init NAME=my-app WITH=api,auth
make module M=api
```

| Module | What it adds                                                                   |
|--------|--------------------------------------------------------------------------------|
| `api`  | API Platform with OpenAPI documentation at `/api`                              |
| `auth` | Security bundle with a Doctrine `User` entity (email, hashed password) and its migration |

### Open in Codespaces

[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/okarabudka/symfony-base)

The dev container generates the project, starts PHP, Nginx and Mailpit and forwards the app (HTTPS) and Mailpit.

### Just try it

```bash
git clone https://github.com/okarabudka/symfony-base.git && cd symfony-base
make up
```

Open https://localhost:8443 (self-signed certificate, accept the browser warning) and Mailpit at http://localhost:8025.
If a port is taken, change `HTTP_PORT`, `HTTPS_PORT` or `MAILPIT_PORT` in `.env` (docker compose reads that file)
and run `make up` again.

### Day-to-day work

| Target                | What it does                                                          |
|-----------------------|-----------------------------------------------------------------------|
| `make init`           | Turn the copy into your project: `make init NAME=my-app [VENDOR=acme] [WITH=api,auth]` |
| `make module`         | Add a module later: `make module M=api\|auth`                         |
| `make sync-template`  | Show template changes; `APPLY=1` takes over infrastructure files      |
| `make up`             | Generate on first run, then start containers, install, migrate        |
| `make down`           | Stop and remove containers                                            |
| `make sh`             | Open a shell in the PHP container                                     |
| `make test`           | Run the test suite (PHPUnit)                                          |
| `make coverage`       | Tests with code coverage (fails below 80 %)                           |
| `make mutate`         | Mutation testing with Infection (fails below 70 % covered MSI)        |
| `make lint`           | PHP CS Fixer, PHPStan, container/YAML/Twig lint, Doctrine mapping     |
| `make fix`            | Fix code style                                                        |
| `make help`           | List all targets                                                      |

### Keep up with the template

`make sync-template` downloads the current template and lists infrastructure files (Docker, Makefile, CI, dev
container, scripts) that are new or changed, plus application files the template provides. `make sync-template
APPLY=1` takes over the infrastructure files. Your own files stay, and application code is never overwritten.

## Development services and ports

| Service | Image                       | Port (host)                     |
|---------|-----------------------------|---------------------------------|
| web     | `nginx:1.27-alpine`         | 8443 (HTTPS), 8080 (HTTP)       |
| php     | `docker/php/Dockerfile`     | —                               |
| mail    | `axllent/mailpit`           | 8025 (UI), SMTP inside network  |
| db      | `mariadb:11.4` (`full`)     | —                               |
| redis   | `redis:7-alpine` (`full`)   | —                               |

To use MariaDB, run `docker compose --profile full up -d` and set `DATABASE_URL` in `.env.local` (an example is in
`.env`).

## Production

`docker/php/Dockerfile` has three production targets: `app` (PHP-FPM), `web` (Nginx with the compiled assets) and
`server` (both in one container, for Kamal). `compose.prod.yaml` runs `app` and `web` with a Messenger worker and the
scheduler:

```bash
echo "APP_SECRET=$(openssl rand -hex 16)" > .env.local
docker compose -f compose.prod.yaml up -d --build --wait
curl http://localhost:8080/health
```

On start, the `app` container runs migrations, sets up the Messenger transports and warms the cache. TLS is expected
to terminate in front of `web`.

### Deploy with Kamal

Every project ships a [Kamal](https://kamal-deploy.org) configuration in `config/deploy.yml`:

```bash
# set the server address and domain in config/deploy.yml, then:
export KAMAL_REGISTRY_PASSWORD=<GitHub token with write:packages> APP_SECRET=<random hex>
kamal setup     # first time
kamal deploy    # every release
```

Kamal runs three roles from the `server` image: `web` (runs migrations), `worker` and `scheduler`.

### Images on GitHub Container Registry

Pushing a tag such as `v1.2.0` builds and publishes `ghcr.io/<owner>/<repo>` (for Kamal) and
`ghcr.io/<owner>/<repo>-app` / `-web` (for `compose.prod.yaml`).

## Repository layout

```
bin/generate               installs the latest Symfony and applies the template
bin/patch-skeleton.php     anchored edits to recipe files
bin/init                   turns a copy into your project
bin/module                 optional modules (api, auth)
bin/sync-template          compares a project with the current template
overlay/                   files the template adds to every project
docker/php/Dockerfile      dev, app, web and server targets
docker/conf/               Nginx, MariaDB and TLS configuration
docker-compose.yml         local development stack
compose.prod.yaml          production-like stack with worker and scheduler
.github/                   CI, release workflow and Dependabot
.devcontainer/             Codespaces and VS Code dev container
```

## Status

Maintained.

## License

MIT, see [LICENSE](LICENSE).
