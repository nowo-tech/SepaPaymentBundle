# FrankenPHP (Dunglas) - PHP 8.5 Alpine
# https://frankenphp.dev/docs/docker/
FROM dunglas/frankenphp:1-php8.5-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    bash

# Install PHP extensions
RUN install-php-extensions intl zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure git safe directory (for Composer path repo when bundle is mounted)
RUN git config --global --add safe.directory /app \
    && git config --global --add safe.directory /var/sepa-payment-bundle

WORKDIR /app

# Custom Caddyfile: HTTP on port 80 (no HTTPS). Caddyfile.dev used when APP_ENV=dev.
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/frankenphp/Caddyfile.dev /etc/frankenphp/Caddyfile.dev

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="/app/vendor/bin:${PATH}"
ENV SERVER_NAME=:80

# Entrypoint: ensure var dirs, when APP_ENV=dev copy Caddyfile.dev (no worker), then run FrankenPHP
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
