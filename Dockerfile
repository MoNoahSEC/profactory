FROM php:8.4-cli-alpine

# Environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install composer and official extension installer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    bash

# Install PHP extensions reliably without compilation errors
RUN install-php-extensions gd pdo_sqlite mbstring zip bcmath opcache

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Fix CRLF line endings for Linux containers
RUN sed -i 's/\r$//' docker-entrypoint.sh && chmod +x docker-entrypoint.sh

# Set directory permissions
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             storage/backups \
             storage/app/public \
             bootstrap/cache \
             database \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database

# Install Composer dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Expose port (Host dynamically supplies $PORT)
ENV PORT=10000
EXPOSE 10000

CMD ["/var/www/html/docker-entrypoint.sh"]
