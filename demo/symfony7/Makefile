# Makefile for SEPA Payment Bundle Demo - Symfony 7
# Run from this directory: make up, make install, etc.

.PHONY: help up down restart build install shell logs test test-coverage update update-bundle ensure-up verify

help:
	@echo "SEPA Payment Bundle Demo - Symfony 7"
	@echo ""
	@echo "  make up             Start containers"
	@echo "  make down            Stop containers"
	@echo "  make restart         Restart containers"
	@echo "  make build           Build image (no cache)"
	@echo "  make install         Install dependencies and link bundle from path"
	@echo "  make shell           Open shell in PHP container"
	@echo "  make logs            Show container logs"
	@echo "  make test            Run tests"
	@echo "  make test-coverage   Run tests with coverage"
	@echo "  make update          Run composer update"
	@echo "  make update-bundle   Update bundle from path repo"
	@echo "  make verify          Ensure up and check HTTP 200"
	@echo ""

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	docker-compose up -d
	@echo "Waiting for container to be ready..."
	@sleep 5
	@echo "Installing dependencies..."
	@docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	@$(MAKE) link-bundle
	@PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	echo "Demo started at: http://localhost:$$PORT"
down:
	docker-compose down

restart: ensure-up
	docker-compose restart

build:
	docker-compose build --no-cache

ensure-up:
	@if ! docker-compose exec -T php true 2>/dev/null; then \
		echo "Starting container..."; \
		docker-compose up -d; \
		sleep 5; \
	fi

# Install deps and link bundle from /var/sepa-payment-bundle (Symfony 7: fix web_profiler routes)
install: ensure-up
	@docker-compose exec -T php sh -c 'rm -rf vendor/nowo-tech/sepa-payment-bundle 2>/dev/null; composer update --no-interaction'
	@docker-compose exec -T php sh -c 'sed -i "s/wdt\.php/wdt.xml/g; s/profiler\.php/profiler.xml/g" config/routes/web_profiler.yaml 2>/dev/null || true'
	@$(MAKE) link-bundle

link-bundle: ensure-up
	@docker-compose exec -T php sh -c 'rm -rf vendor/nowo-tech/sepa-payment-bundle; ln -sf /var/sepa-payment-bundle vendor/nowo-tech/sepa-payment-bundle && composer run-script post-install-cmd'

update: ensure-up
	docker-compose exec -T php composer update --no-interaction

update-bundle: ensure-up
	docker-compose exec -T php composer update nowo-tech/sepa-payment-bundle --no-interaction
	@$(MAKE) link-bundle
	docker-compose exec -T php php bin/console cache:clear
	@echo "Bundle updated. Reload the page in the browser."

shell: ensure-up
	docker-compose exec php sh

logs:
	docker-compose logs -f

test: ensure-up
	docker-compose exec php composer test

test-coverage: ensure-up
	docker-compose exec php composer test-coverage

verify: ensure-up
	@PORT=$$(grep '^PORT=' .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=8001; \
	code=$$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$$PORT 2>/dev/null || echo "000"); \
	if [ "$$code" = "200" ]; then echo "✅ Demo responding (HTTP $$code)"; else echo "❌ Demo not responding (HTTP $$code)"; exit 1; fi

# REQ-MAKE-008: update-deps (REQ-MAKE-008)
COMPOSE := docker-compose
SERVICE_PHP := php
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../..)
include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-update-deps.mk
