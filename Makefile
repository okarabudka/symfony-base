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
	@make print-color-green TEXT='composer-install'
	@printf '		install composer dependencies\n'

up-containers:
	@make docker-compose-exec COMPOSE_CMD="up -d --build"

composer-install:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "composer install -n"'

docker-exec:
	@docker exec -it symfony_docker_$(CONTAINER) $(CONTAINER_CMD)

docker-compose-exec:
	@docker compose $(COMPOSE_CMD)

print-color-green:
	@printf '\033[0;32m${TEXT}\033[0m'

print-color-blue:
	@printf '\033[0;36m${TEXT}\033[0m'
