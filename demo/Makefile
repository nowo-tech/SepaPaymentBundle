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
.PHONY: $(foreach demo,$(DEMOS),up-$(demo) down-$(demo) restart-$(demo) build-$(demo) install-$(demo) update-$(demo) update-bundle-$(demo) shell-$(demo) logs-$(demo) test-$(demo) test-coverage-$(demo) verify-$(demo))
.PHONY: up down restart build install update update-bundle test shell logs test-coverage verify release-verify

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
		echo "  restart-$$demo   Restart $$version demo containers"; \
		echo "  build-$$demo     Rebuild $$version image (no cache)"; \
		echo "  install-$$demo   Install dependencies for $$version demo"; \
		echo "  update-$$demo   Run composer update in $$version demo"; \
		echo "  update-bundle-$$demo  Update bundle from path in $$version demo"; \
		echo "  shell-$$demo     Open shell in $$version PHP container"; \
		echo "  logs-$$demo      Show $$version container logs"; \
		echo "  test-$$demo      Run tests for $$version demo"; \
		echo "  test-coverage-$$demo  Run tests with coverage for $$version demo"; \
		echo "  verify-$$demo    Ensure up and check HTTP 200"; \
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
	@echo "  restart <demo>     Restart a specific demo"
	@echo "  build <demo>       Rebuild image for a specific demo"
	@echo "  install <demo>     Install dependencies (e.g., make install DEMO=symfony6)"
	@echo "  update <demo>      Run composer update in a specific demo"
	@echo "  update-bundle <demo>  Update bundle from path in a specific demo"
	@echo "  test <demo>        Run tests (e.g., make test DEMO=symfony6)"
	@echo "  shell <demo>       Open shell in a specific demo container"
	@echo "  logs <demo>        Show logs of a specific demo"
	@echo ""
	@echo "Other commands:"
	@echo "  clean              Remove vendor and cache from all demos"
	@echo "  release-verify     Start each demo, healthcheck (HTTP 200), then stop (for release-check)"
	@echo ""

# Delegate to each demo's Makefile
up-%:
	@$(MAKE) -C $* up

down-%:
	@$(MAKE) -C $* down

restart-%:
	@$(MAKE) -C $* restart

build-%:
	@$(MAKE) -C $* build

install-%:
	@$(MAKE) -C $* install

update-%:
	@$(MAKE) -C $* update

update-bundle-%:
	@$(MAKE) -C $* update-bundle

shell-%:
	@$(MAKE) -C $* shell

logs-%:
	@$(MAKE) -C $* logs

test-%:
	@$(MAKE) -C $* test

test-coverage-%:
	@$(MAKE) -C $* test-coverage

verify-%:
	@$(MAKE) -C $* verify

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

restart:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make restart DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) restart-$(DEMO)

build:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make build DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) build-$(DEMO)

install:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make install DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) install-$(DEMO)

update:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make update DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) update-$(DEMO)

update-bundle:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: DEMO variable is required. Usage: make update-bundle DEMO=symfony6"; \
		exit 1; \
	fi
	@$(MAKE) update-bundle-$(DEMO)

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
		$(MAKE) update-bundle-$$demo; \
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

# release-check: test-coverage-all, then release-verify
release-check: test-coverage-all release-verify
	@echo "✅ All demos passed release-check."

# Release verify: for each demo, up → sleep → healthcheck (HTTP 200) → down
release-verify:
	@for demo in $(DEMOS); do \
		port=$$(grep '^PORT=' $$demo/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$port" ] && port=$$(grep '^PORT=' $$demo/.env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$port" ] && port=8001; \
		echo "🔍 Release verify: $$demo (port $$port)..."; \
		$(MAKE) up-$$demo; \
		echo "⏳ Waiting for $$demo to be ready..."; sleep 10; \
		code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port 2>/dev/null || echo "000"); \
		$(MAKE) down-$$demo 2>/dev/null || true; \
		if [ "$$code" != "200" ]; then echo "❌ $$demo healthcheck failed (HTTP $$code)"; exit 1; fi; \
		echo "✅ $$demo OK (HTTP $$code)"; \
	done
	@echo "✅ All demos passed release-verify."

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


# REQ-MAKE-008: update-deps-all (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/..)
include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-aggregate-update-deps.mk
