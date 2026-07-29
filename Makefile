# Makefile for SEPA Payment Bundle
# Simplifies Docker commands for development

.PHONY: help up down down-dev build shell install test test-coverage coverage-php-percent cs-check cs-fix qa clean assets ensure-up rector rector-dry phpstan release-check release-check-demos demo-smoke composer-sync update validate validate-translations setup-hooks check-no-cursor-coauthor strip-cursor-coauthor-from-history

# Default target
help:
	@echo "SEPA Payment Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up              Start Docker container"
	@echo "  down            Stop Docker container"
	@echo "  down-dev        Stop root compose (dev) and remove orphans"
	@echo "  build           Rebuild Docker image (no cache)"
	@echo "  shell           Open shell in container"
	@echo "  install         Install Composer dependencies"
	@echo "  assets          No frontend assets in this bundle (no-op)"
	@echo "  test            Run PHPUnit tests"
	@echo "  test-coverage   Run tests with code coverage"
	@echo "  cs-check        Check code style"
	@echo "  cs-fix          Fix code style"
	@echo "  rector          Apply Rector refactoring"
	@echo "  rector-dry      Run Rector in dry-run mode"
	@echo "  phpstan         Run PHPStan static analysis"
	@echo "  qa              Run all QA checks (cs-check + test)"
	@echo "  release-check   Pre-release: composer-sync, cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo healthchecks"
	@echo "  demo-smoke      REQ-TEST-011: boot primary demo + HTTP 200"
	@echo "  composer-sync   Validate composer.json and align composer.lock"
	@echo "  clean           Remove vendor and cache"
	@echo "  update          Update composer.lock (composer update)"
	@echo "  validate        Run composer validate --strict"
	@echo "  setup-hooks     Install git pre-commit hooks"
	@echo ""
	@echo "Demos:"
	@echo "  (use make -C demo or make -C demo/symfonyX)"
	@echo ""

# Rebuild Docker image (no cache)
build:
	$(COMPOSE) build --no-cache

# Build and start container
up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo "Installing dependencies..."
	$(COMPOSE) exec php composer install --no-interaction
	@echo "✅ Container ready!"

# Stop container
down:
	$(COMPOSE) down

down-dev:
	$(COMPOSE) down --remove-orphans

# Ensure root container is running (start if not). Used by cs-fix, cs-check, qa, install, test, test-coverage.
ensure-up:
	@if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
		echo "Starting container (root $(COMPOSE))..."; \
		$(COMPOSE) up -d; \
		sleep 3; \
		$(COMPOSE) exec -T php composer install --no-interaction; \
	fi

# Open shell in container
shell:
	$(COMPOSE) exec php sh

# Install dependencies
install: ensure-up
	$(COMPOSE) exec -T php composer install

# Run tests (no -T so TTY is allocated and PHPUnit can show colors in console)
test: ensure-up
	$(COMPOSE) exec php composer test

# Run tests with coverage (no -T so coverage is shown in console with colors)
test-coverage: ensure-up
	$(COMPOSE) exec php composer test-coverage | tee coverage-php.txt
	./.scripts/php-coverage-percent.sh coverage-php.txt

# Check code style
cs-check: ensure-up
	$(COMPOSE) exec -T php composer cs-check

# Fix code style
cs-fix: ensure-up
	$(COMPOSE) exec -T php composer cs-fix

# Run Rector (apply refactoring)
rector: ensure-up
	$(COMPOSE) exec -T php composer rector

# Run Rector in dry-run mode
rector-dry: ensure-up
	$(COMPOSE) exec -T php composer rector-dry

# Run PHPStan static analysis
phpstan: ensure-up
	$(COMPOSE) exec -T php composer phpstan

# Validate composer.json and verify composer.lock matches (does not rewrite the lock file)
composer-sync: ensure-up
	$(COMPOSE) exec -T php composer validate --strict
	$(COMPOSE) exec -T php composer install --dry-run --no-interaction

# Update composer.lock
update: ensure-up
	$(COMPOSE) exec -T php composer update --no-interaction

# Validate composer.json
validate: ensure-up
	$(COMPOSE) exec -T php composer validate --strict

# Run all QA
qa: ensure-up
	$(COMPOSE) exec -T php composer qa

# Pre-release: composer-sync, cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo healthchecks
release-check: check-no-cursor-coauthor ensure-up composer-sync cs-fix cs-check rector-dry phpstan test-coverage release-check-demos

release-check-demos:
	@$(MAKE) -C demo release-check

demo-smoke:
	@$(MAKE) -C demo demo-smoke

# No frontend assets in this bundle
assets:
	@echo "No frontend assets in this bundle."

# Clean vendor and cache
clean:
	rm -rf vendor
	rm -rf .phpunit.cache
	rm -rf coverage
	rm -f coverage.xml
	rm -f .php-cs-fixer.cache


# Validate bundle translation YAML files
validate-translations: ensure-up
	$(COMPOSE) exec -T php php -r 'require "vendor/autoload.php"; foreach (glob("src/Resources/translations/*.yaml") as $$f) { Symfony\Component\Yaml\Yaml::parseFile($$f); echo "OK: " . $$f . PHP_EOL; }'

check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

setup-hooks:
	@chmod +x .githooks/pre-commit 2>/dev/null || true
	@chmod +x .githooks/commit-msg 2>/dev/null || true
	@git config core.hooksPath .githooks
	@echo "✅ Git hooks installed (.githooks — includes commit-msg for REQ-GIT-001)."

# REQ-MAKE-008: update-deps (REQ-MAKE-008)
# Prefer Compose V2 plugin (GitHub Actions / modern Docker Desktop); fall back to docker-compose V1 (REQ-MAKE-010).
# Absolute docker path avoids shadowing by a local docker/ directory when PATH contains ".".
DOCKER_BIN := $(shell PATH="/usr/local/bin:/usr/bin:/bin:$$PATH" command -v docker 2>/dev/null)
ifeq ($(DOCKER_BIN),)
COMPOSE := docker-compose
else
COMPOSE := $(shell $(DOCKER_BIN) compose version >/dev/null 2>&1 && echo "$(DOCKER_BIN) compose" || echo "docker-compose")
endif
SERVICE_PHP := php
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
# Optional: monorepo helper absent on standalone GitHub Actions checkout (REQ-MAKE-009).
-include $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk

strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main
