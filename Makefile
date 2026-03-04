# Makefile for SEPA Payment Bundle
# Simplifies Docker commands for development

.PHONY: help up down build shell install test test-coverage cs-check cs-fix qa clean assets ensure-up rector rector-dry phpstan release-check release-check-demos composer-sync update validate

# Default target
help:
	@echo "SEPA Payment Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up              Start Docker container"
	@echo "  down            Stop Docker container"
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
	@echo "  composer-sync   Validate composer.json and align composer.lock"
	@echo "  clean           Remove vendor and cache"
	@echo "  update          Update composer.lock (composer update)"
	@echo "  validate        Run composer validate --strict"
	@echo ""
	@echo "Demos:"
	@echo "  (use make -C demo or make -C demo/symfonyX)"
	@echo ""

# Rebuild Docker image (no cache)
build:
	docker-compose build --no-cache

# Build and start container
up:
	docker-compose build
	docker-compose up -d
	@echo "Installing dependencies..."
	docker-compose exec php composer install --no-interaction
	@echo "✅ Container ready!"

# Stop container
down:
	docker-compose down

# Ensure root container is running (start if not). Used by cs-fix, cs-check, qa, install, test, test-coverage.
ensure-up:
	@if ! docker-compose exec -T php true 2>/dev/null; then \
		echo "Starting container (root docker-compose)..."; \
		docker-compose up -d; \
		sleep 3; \
		docker-compose exec -T php composer install --no-interaction; \
	fi

# Open shell in container
shell:
	docker-compose exec php sh

# Install dependencies
install: ensure-up
	docker-compose exec -T php composer install

# Run tests (no -T so TTY is allocated and PHPUnit can show colors in console)
test: ensure-up
	docker-compose exec php composer test

# Run tests with coverage (no -T so coverage is shown in console with colors)
test-coverage: ensure-up
	docker-compose exec php composer test-coverage

# Check code style
cs-check: ensure-up
	docker-compose exec -T php composer cs-check

# Fix code style
cs-fix: ensure-up
	docker-compose exec -T php composer cs-fix

# Run Rector (apply refactoring)
rector: ensure-up
	docker-compose exec -T php composer rector

# Run Rector in dry-run mode
rector-dry: ensure-up
	docker-compose exec -T php composer rector-dry

# Run PHPStan static analysis
phpstan: ensure-up
	docker-compose exec -T php composer phpstan

# Validate composer.json and align composer.lock
composer-sync: ensure-up
	docker-compose exec -T php composer validate --strict
	docker-compose exec -T php composer update --no-install

# Update composer.lock
update: ensure-up
	docker-compose exec -T php composer update --no-interaction

# Validate composer.json
validate: ensure-up
	docker-compose exec -T php composer validate --strict

# Run all QA
qa: ensure-up
	docker-compose exec -T php composer qa

# Pre-release: composer-sync, cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo healthchecks
release-check: ensure-up composer-sync cs-fix cs-check rector-dry phpstan test-coverage release-check-demos

release-check-demos:
	@$(MAKE) -C demo release-verify

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

