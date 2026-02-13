# Makefile

SHELL = /bin/bash

ifndef VERBOSE
MAKEFLAGS += --no-print-directory
endif

help:
	@printf '		'
	@make print-color-blue TEXT='** Available commands **\n\n'
	@make print-color-green TEXT='up-containers'
	@printf '		running docker containers\n'
	@make print-color-green TEXT='down'
	@printf '		stop docker containers\n'
	@make print-color-green TEXT='logs'
	@printf '		show logs\n'
	@make print-color-green TEXT='bash'
	@printf '		open shell in app container\n'
	@make print-color-green TEXT='composer-install'
	@printf '		install composer dependencies\n'
	@make print-color-green TEXT='migrate'
	@printf '		run migrations\n'
	@make print-color-green TEXT='test'
	@printf '		run test suite\n'
	@make print-color-green TEXT='lint'
	@printf '		run php lint\n'
	@make print-color-green TEXT='phpstan'
	@printf '		run phpstan\n'
	@make print-color-green TEXT='export-app'
	@printf '		export generated app to ./app\n'

up-containers:
	@make docker-compose-exec COMPOSE_CMD="up -d --build"

down:
	@make docker-compose-exec COMPOSE_CMD="down"

logs:
	@make docker-compose-exec COMPOSE_CMD="logs -f --tail=200"

composer-install:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "composer install -n"'

composer:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "composer $(ARGS)"'

bash:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash'

migrate:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "php bin/console doctrine:migrations:migrate -n"'

test:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "php bin/phpunit"'

lint:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "find src tests -name \"*.php\" -print0 | xargs -0 -n1 php -l"'

phpstan:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "vendor/bin/phpstan analyse"'

export-app:
	@mkdir -p app
	@docker compose run --rm -v "$$(pwd)/app:/export" app bash -c "cp -a /var/www/. /export"

docker-exec:
	@docker compose exec -it $(CONTAINER) $(CONTAINER_CMD)

docker-compose-exec:
	@docker compose $(COMPOSE_CMD)

print-color-green:
	@printf '\033[0;32m${TEXT}\033[0m'

print-color-blue:
	@printf '\033[0;36m${TEXT}\033[0m'
