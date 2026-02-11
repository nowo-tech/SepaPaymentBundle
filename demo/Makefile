# Makefile for SEPA Payment Bundle Demo
# Each demo has its own docker-compose.yml and can be run independently

# Define available demos
DEMOS := symfony6 symfony7 symfony8
DEMO_DIRS := $(DEMOS)

# Helper function to get port from .env file (defaults to 8001)
get-port = $(shell grep -E '^PORT=' $(1)/.env 2>/dev/null | cut -d '=' -f2 || echo "8001")

# Helper function to get demo version name
get-version = $(shell echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/')

.PHONY: help clean test-all test-coverage-all verify-all check-all
.PHONY: $(foreach demo,$(DEMOS),up-$(demo) down-$(demo) install-$(demo) shell-$(demo) logs-$(demo) test-$(demo) test-coverage-$(demo) verify-$(demo))
.PHONY: up down install test shell logs test-coverage verify

# Default target
help:
	@echo "SEPA Payment Bundle - Demo Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@for demo in $(DEMOS); do \
		version=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
		echo "$$version Demo (Port 8001):"; \
		echo "  up-$$demo        Start $$version demo containers"; \
		echo "  down-$$demo      Stop $$version demo containers"; \
		echo "  install-$$demo   Install dependencies for $$version demo"; \
		echo "  shell-$$demo     Open shell in $$version PHP container"; \
		echo "  logs-$$demo      Show $$version container logs"; \
		echo "  test-$$demo      Run tests for $$version demo"; \
		echo "  test-coverage-$$demo  Run tests with coverage for $$version demo"; \
		echo ""; \
	done
	@echo "Testing:"
	@echo "  test-all           Run tests for all demos"
	@echo "  test-coverage-all  Run tests with coverage for all demos"
	@echo "  verify-all         Verify all demos are running correctly (HTTP 200)"
	@echo ""
	@echo "Generic commands (use with DEMO=<name>):"
	@echo "  up <demo>         Start a specific demo (e.g., make up DEMO=symfony6)"
	@echo "  down <demo>        Stop a specific demo (e.g., make down DEMO=symfony6)"
	@echo "  install <demo>     Install dependencies (e.g., make install DEMO=symfony6)"
	@echo "  test <demo>        Run tests (e.g., make test DEMO=symfony6)"
	@echo "  shell <demo>       Open shell in a specific demo container"
	@echo "  logs <demo>        Show logs of a specific demo"
	@echo ""
	@echo "Other commands:"
	@echo "  clean              Remove vendor and cache from all demos"
	@echo ""

# Generate up targets for each demo
define up-target-template
up-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🚀 Starting $$$$VERSION demo..."; \
	if ! command -v docker-compose >/dev/null 2>&1 && ! command -v docker compose >/dev/null 2>&1; then \
		echo "❌ Error: docker-compose or 'docker compose' is not available"; \
		echo "Please install Docker Desktop and enable WSL 2 integration"; \
		echo "See: https://docs.docker.com/go/wsl2/"; \
		exit 1; \
	fi; \
	cd $(1) && \
	if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi && \
	PORT=$$$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
	echo "🔍 Checking port $$$$PORT..."; \
	CONTAINER=$$$$(docker ps --format "{{.Names}}" --filter "publish=$$$$PORT" 2>/dev/null | head -1); \
	if [ -n "$$$$CONTAINER" ]; then \
		echo "⚠️  Port $$$$PORT is in use by container: $$$$CONTAINER"; \
		echo "🛑 Stopping container $$$$CONTAINER..."; \
		docker stop $$$$CONTAINER 2>/dev/null || true; \
		docker rm $$$$CONTAINER 2>/dev/null || true; \
		echo "✅ Port $$$$PORT is now free"; \
	fi && \
	if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi && \
	$$$$DOCKER_COMPOSE_CMD build && \
	$$$$DOCKER_COMPOSE_CMD up -d && \
	echo "⏳ Waiting for demo to be ready..." && \
	sleep 5 && \
	MAX_ATTEMPTS=12; \
	ATTEMPT=1; \
	while [ $$$$ATTEMPT -le $$$$MAX_ATTEMPTS ]; do \
		if curl -s -o /dev/null -w "%{http_code}" http://localhost:$$$$PORT 2>/dev/null | grep -q "200"; then \
			echo "✅ $$$$VERSION demo is running and responding at http://localhost:$$$$PORT"; \
			exit 0; \
		fi; \
		echo "⏳ Attempt $$$$ATTEMPT/$$$$MAX_ATTEMPTS: Waiting for demo to respond..."; \
		sleep 5; \
		ATTEMPT=$$$$((ATTEMPT + 1)); \
	done; \
	echo "❌ Demo failed to respond after $$$$MAX_ATTEMPTS attempts"; \
	exit 1

down-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🛑 Stopping $$$$VERSION demo..."; \
	if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD down

install-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "📦 Installing dependencies for $$$$VERSION demo..."; \
	if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD up -d && $$$$DOCKER_COMPOSE_CMD exec php sh -c 'rm -rf vendor/nowo-tech/sepa-payment-bundle 2>/dev/null; composer update --no-interaction && (case "$(1)" in symfony6|symfony7) sed -i "s/wdt\.php/wdt.xml/g; s/profiler\.php/profiler.xml/g" config/routes/web_profiler.yaml 2>/dev/null || true ;; esac) && rm -rf vendor/nowo-tech/sepa-payment-bundle && ln -s /var/sepa-payment-bundle vendor/nowo-tech/sepa-payment-bundle && composer run-script post-install-cmd'

update-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🔄 Updating composer.lock for $$$$VERSION demo..."; \
	if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD exec php composer update --no-interaction

shell-$(1):
	@if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD exec php sh

logs-$(1):
	@if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD logs -f

test-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🧪 Running tests for $$$$VERSION demo..."; \
	if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD exec php composer test

test-coverage-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "📊 Running tests with coverage for $$$$VERSION demo..."; \
	if command -v docker-compose >/dev/null 2>&1; then \
		DOCKER_COMPOSE_CMD="docker-compose"; \
	else \
		DOCKER_COMPOSE_CMD="docker compose"; \
	fi; \
	cd $(1) && $$$$DOCKER_COMPOSE_CMD exec php composer test-coverage

verify-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🔍 Verifying $$$$VERSION demo..."; \
	cd $(1) && \
	if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi && \
	PORT=$$$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
	$(MAKE) up-$(1) && \
	if curl -s -o /dev/null -w "%{http_code}" http://localhost:$$$$PORT | grep -q "200"; then \
		echo "✅ $$$$VERSION demo verified successfully"; \
	else \
		echo "❌ $$$$VERSION demo verification failed"; \
		exit 1; \
	fi
endef

$(foreach demo,$(DEMOS),$(eval $(call up-target-template,$(demo))))

# Generic commands that work with DEMO variable
up:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make up DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) up-$(DEMO)

down:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make down DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) down-$(DEMO)

install:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make install DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) install-$(DEMO)

test:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make test DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) test-$(DEMO)

test-coverage:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make test-coverage DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) test-coverage-$(DEMO)

shell:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make shell DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) shell-$(DEMO)

logs:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make logs DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) logs-$(DEMO)

verify:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make verify DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) verify-$(DEMO)

# Test all demos
test-all:
	@echo "🧪 Running tests for all demos..."
	@FAILED=0; \
	for demo in $(DEMOS); do \
		echo ""; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		echo "Testing $$demo..."; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		if $(MAKE) test-$$demo; then \
			echo "✅ $$demo tests passed"; \
		else \
			echo "❌ $$demo tests failed"; \
			FAILED=$$((FAILED + 1)); \
		fi; \
	done; \
	if [ $$FAILED -gt 0 ]; then \
		echo ""; \
		echo "❌ $$FAILED demo(s) failed tests"; \
		exit 1; \
	else \
		echo ""; \
		echo "✅ All demos passed tests"; \
	fi

# Test coverage for all demos
test-coverage-all:
	@echo "📊 Running tests with coverage for all demos..."
	@FAILED=0; \
	for demo in $(DEMOS); do \
		echo ""; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		echo "Testing $$demo with coverage..."; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		if $(MAKE) test-coverage-$$demo; then \
			echo "✅ $$demo coverage generated"; \
		else \
			echo "❌ $$demo coverage failed"; \
			FAILED=$$((FAILED + 1)); \
		fi; \
	done; \
	if [ $$FAILED -gt 0 ]; then \
		echo ""; \
		echo "❌ $$FAILED demo(s) failed coverage"; \
		exit 1; \
	else \
		echo ""; \
		echo "✅ All demos coverage generated"; \
	fi

# Verify all demos
verify-all:
	@echo "🔍 Verifying all demos..."
	@FAILED=0; \
	SUCCESSFUL=0; \
	for demo in $(DEMOS); do \
		echo ""; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		VERSION=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
		echo "📦 Processing $$VERSION demo ($$demo)"; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		if $(MAKE) verify-$$demo; then \
			SUCCESSFUL=$$((SUCCESSFUL + 1)); \
		else \
			FAILED=$$((FAILED + 1)); \
		fi; \
		$(MAKE) down-$$demo || true; \
	done; \
	echo ""; \
	echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
	echo "📊 Verification Summary"; \
	echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
	if [ $$FAILED -gt 0 ]; then \
		echo "❌ Failed: $$FAILED/$$(echo $(DEMOS) | wc -w)"; \
		echo "✅ Successful: $$SUCCESSFUL/$$(echo $(DEMOS) | wc -w)"; \
		exit 1; \
	else \
		echo "✅ Successful: $$SUCCESSFUL/$$(echo $(DEMOS) | wc -w)"; \
		echo "✅ All demos verified successfully!"; \
	fi

# Clean all demos
clean:
	@echo "🧹 Cleaning all demos..."
	@for demo in $(DEMOS); do \
		echo "Cleaning $$demo..."; \
		rm -rf $$demo/vendor; \
		rm -rf $$demo/var; \
		rm -rf $$demo/.phpunit.cache; \
		rm -rf $$demo/coverage; \
		rm -f $$demo/coverage.xml; \
	done
	@echo "✅ Clean completed"

