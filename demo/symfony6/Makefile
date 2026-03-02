# Makefile for SEPA Payment Bundle Demo - Symfony 6.4
# Run from this directory: make up, make install, etc.

COMPOSE_FILE := docker-compose.yml
COMPOSE := docker-compose -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down build install shell logs test test-coverage update-bundle ensure-up

help:
	@echo "SEPA Payment Bundle Demo - Symfony 6.4"
	@echo ""
	@echo "  make up             Start containers (port from .env PORT, default 8001)"
	@echo "  make down           Stop containers"
	@echo "  make build          Build image (no cache)"
	@echo "  make install        Install dependencies (composer install in container)"
	@echo "  make shell          Open shell in PHP container"
	@echo "  make logs           Show container logs"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage"
	@echo "  make update-bundle  Update bundle from path repo and clear cache"
	@echo ""

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	$(COMPOSE) up -d
	@echo "Waiting for container to be ready..."
	@sleep 5
	@echo "Installing dependencies..."
	@$(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 $(SERVICE_PHP) composer install --no-interaction
	@echo "App: http://localhost:$${PORT:-8001}"

down:
	$(COMPOSE) down

restart: ensure-up
	$(COMPOSE) restart

build:
	$(COMPOSE) build --no-cache

ensure-up:
	@if ! $(COMPOSE) exec -T $(SERVICE_PHP) true 2>/dev/null; then \
		echo "Starting container..."; \
		$(COMPOSE) up -d; \
		sleep 5; \
	fi

install: ensure-up
	$(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 $(SERVICE_PHP) composer install --no-interaction

shell: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) sh

logs:
	$(COMPOSE) logs -f

test: ensure-up
	$(COMPOSE) exec -e APP_ENV=test $(SERVICE_PHP) composer test

test-coverage: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) composer test-coverage

update-bundle: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) composer update nowo-tech/sepa-payment-bundle --no-interaction
	$(COMPOSE) exec -T $(SERVICE_PHP) php bin/console cache:clear
	@echo "Bundle updated."
