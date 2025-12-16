# SEPA Payment Bundle - Demo

This directory contains three demo projects, one for each supported Symfony version (6.4, 7.0, and 8.0), demonstrating the usage of the SEPA Payment Bundle.

## Features

- Three separate demo projects for Symfony 6.4, 7.0, and 8.0
- Demo pages showing IBAN validation, mandate management, and remesa generation
- Docker setup for easy development
- Independent Docker containers for each demo
- Complete test suites for each demo

## Requirements

- Docker and Docker Compose
- Or PHP 8.1+ to 8.5 (8.2+ for Symfony 8.0) and Composer (for local development)

## Quick Start with Docker

Each demo has its own `docker-compose.yml` and can be run independently. You can start any demo you want:

**Important**: Before starting a demo, copy `.env.example` to `.env`:
```bash
cd demo/demo-symfony6
cp .env.example .env
# Optionally generate a new APP_SECRET: openssl rand -hex 32
# The .env.example includes: APP_ENV=dev, APP_SECRET (placeholder), APP_DEBUG=1, PORT=8001
```

### Symfony 6.4 Demo

```bash
# Navigate to the demo directory
cd demo/demo-symfony6

# Copy .env.example to .env if not already done
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (port configured in .env file)
```

Or using the Makefile from the `demo/` directory:

```bash
cd demo
make up-symfony6
make install-symfony6

# Or verify that the demo is running correctly
make verify DEMO=symfony6
```

### Symfony 7.0 Demo

```bash
cd demo
make up-symfony7
make install-symfony7
```

### Symfony 8.0 Demo

```bash
cd demo
make up-symfony8
make install-symfony8
```

## Testing

Each demo includes its own test suite to verify that the SEPA Payment Bundle works correctly with the specific Symfony version.

### Run Tests

```bash
cd demo

# Run tests for a specific demo
make test-symfony6
make test-symfony7
make test-symfony8

# Run all tests
make test-all
```

### Run Tests with Code Coverage

```bash
cd demo

# Run tests with coverage for a specific demo
make test-coverage-symfony6
make test-coverage-symfony7
make test-coverage-symfony8

# Run all demos with coverage
make test-coverage-all
```

## Verification

You can verify that all demos are running and responding correctly:

```bash
cd demo

# Verify all demos (starts and checks each one sequentially)
make verify-all

# Or verify a specific demo
make verify DEMO=symfony6
```

## What's Included

Each demo includes:

- **DemoController**: A controller demonstrating IBAN validation, mandate management, and remesa generation
- **Docker Setup**: Complete Docker configuration with PHP-FPM and Nginx
- **Dockerfile**: Custom PHP-FPM image with Composer pre-installed
- **Tests**: Complete test suite verifying bundle integration

## License

This demo is part of the SEPA Payment Bundle project and follows the same MIT license.

