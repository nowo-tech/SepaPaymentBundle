FROM dunglas/frankenphp:1-php8.5-alpine

# Install additional PHP extensions (zip is common for Composer)
RUN install-php-extensions zip pcov

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install git and configure safe directory (for Composer in mounted repos)
RUN apk add --no-cache git \
    && git config --global --add safe.directory /app

# Set working directory
WORKDIR /app

# Set environment
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="/app/vendor/bin:${PATH}"
ENV XDEBUG_MODE=coverage
