# Makefile for SEPA Payment Bundle
# Simplifies Docker commands for development

.PHONY: help up down shell install test test-coverage cs-check cs-fix qa clean test-up test-down test-shell ensure-up assets release-check release-check-demos composer-sync

# Default target
help:
	@echo "SEPA Payment Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up            Start Docker container"
	@echo "  down          Stop Docker container"
	@echo "  shell         Open shell in container"
	@echo "  install       Install Composer dependencies"
	@echo "  test          Run PHPUnit tests"
	@echo "  test-coverage Run tests with code coverage"
	@echo "  test-up       Start test container"
	@echo "  test-down     Stop test container"
	@echo "  test-shell    Open shell in test container"
	@echo "  cs-check      Check code style"
	@echo "  cs-fix        Fix code style"
	@echo "  qa            Run all QA checks (cs-check + test)"
	@echo "  release-check Pre-release: cs-fix, cs-check, test-coverage, demo healthchecks"
	@echo "  composer-sync Validate composer.json and align composer.lock (no install)"
	@echo "  clean         Remove vendor and cache"
	@echo "  assets        No frontend assets in this bundle (no-op)"
	@echo ""

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

# Ensure container is running (start if not). Used by install, shell, test, cs-check, etc.
ensure-up:
	@if ! docker-compose exec -T php true 2>/dev/null; then \
		echo "Starting container..."; \
		docker-compose up -d; \
		sleep 3; \
	fi

# Open shell in container
shell: ensure-up
	docker-compose exec php sh

# Install dependencies
install: ensure-up
	docker-compose exec php composer install

# Run tests
test: ensure-up
	docker-compose exec php composer test

# Run tests with coverage
test-coverage: ensure-up
	docker-compose exec php composer test-coverage

# Start container (same as up; single compose)
test-up:
	$(MAKE) up

# Stop container
test-down:
	docker-compose down

# Open shell in container
test-shell:
	docker-compose exec php sh

# Check code style
cs-check: ensure-up
	docker-compose exec php composer cs-check

# Fix code style
cs-fix: ensure-up
	docker-compose exec php composer cs-fix

# Run all QA
qa: ensure-up
	docker-compose exec php composer qa

# Pre-release: cs-fix, cs-check, test-coverage, demo healthchecks
release-check: ensure-up composer-sync cs-fix cs-check test-coverage release-check-demos

release-check-demos:
	@$(MAKE) -C demo release-verify

composer-sync: ensure-up
	docker-compose exec -T php composer validate --strict
	docker-compose exec -T php composer update --no-install

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

