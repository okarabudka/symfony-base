# Makefile — local Docker workflow. Run `make help` for the list of targets.

SHELL := /bin/bash
.DEFAULT_GOAL := help

export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose
comma := ,
# -T keeps exec usable without a TTY (CI, scripts).
EXEC := $(COMPOSE) exec -T php

.PHONY: help init module app sync-template certs env up down sh test coverage mutate lint fix

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

init: app ## Turn this copy into your project: make init NAME=my-app [VENDOR=acme] [WITH=api,auth]
	@sh bin/init "$(NAME)" "$(VENDOR)"
	@for m in $(subst $(comma), ,$(WITH)); do $(MAKE) --no-print-directory module M=$$m || exit 1; done

module: ## Add an optional module later: make module M=api|auth
	@test -n "$(M)" || { echo "Usage: make module M=api|auth"; exit 1; }
	$(COMPOSE) run --rm --no-deps php sh bin/module $(M)

app: ## Install the latest Symfony and apply the template (first run only)
	@test -f bin/console || { $(COMPOSE) build php && $(COMPOSE) run --rm --no-deps php sh bin/generate; }

sync-template: ## Show template changes since this project started; APPLY=1 takes over infrastructure
	@sh bin/sync-template

certs: ## Create a self-signed TLS certificate (skipped if present)
	@sh docker/conf/ssl/generate.sh

env: ## Create .env.local with a generated APP_SECRET (skipped if present)
	@test -f .env.local || printf 'APP_SECRET=%s\n' "$$(openssl rand -hex 16)" > .env.local

up: certs app env ## Build and start containers, install dependencies, migrate
	$(COMPOSE) up -d --build
	$(EXEC) composer install --no-interaction
	$(EXEC) php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(EXEC) php bin/console messenger:setup-transports --no-interaction

down: ## Stop and remove containers
	$(COMPOSE) down

sh: ## Open a shell in the PHP container
	$(COMPOSE) exec php sh

test: ## Run the test suite (PHPUnit)
	$(EXEC) composer test

coverage: ## Run the tests with code coverage (fails below 80 %)
	$(EXEC) composer coverage

mutate: ## Run mutation testing with Infection (fails below 70 % MSI)
	$(EXEC) composer mutate

lint: ## Code style (PHP CS Fixer), PHPStan, container/YAML/Twig lint, Doctrine mapping
	$(EXEC) composer lint

fix: ## Fix code style (PHP CS Fixer)
	$(EXEC) composer fix
