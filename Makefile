.PHONY: build up down clean prune bash check-env

ENV_FILE ?= .env.dev
COMPOSE := docker compose --env-file $(ENV_FILE) -f compose.dev.yaml
UID := $(shell id -u)
GID := $(shell id -g)

check-env:
	@test -f $(ENV_FILE) || (echo "$(ENV_FILE) not found. Copy .env.dev.example to $(ENV_FILE)."; exit 1)

build: check-env
	UID=$(UID) GID=$(GID) $(COMPOSE) build

up: check-env
	UID=$(UID) GID=$(GID) $(COMPOSE) up -d

down: check-env
	$(COMPOSE) down

clean: check-env
	$(COMPOSE) down -v

prune:
	docker system prune -f

bash: check-env
	$(COMPOSE) exec php bash
-include .env.sonar
export
sonar:
	sonar-scanner \
          -Dsonar.projectKey=$(SONAR_PROJECT_KEY) \
          -Dsonar.host.url=$(SONAR_URL) \
          -Dsonar.token=$(SONAR_TOKEN)