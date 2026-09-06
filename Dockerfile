FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip bcmath gd opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/backups database \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database

# Install dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Expose Render Port (Render supplies $PORT dynamically)
ENV PORT=10000
EXPOSE 10000

# Copy entrypoint script
RUN chmod +x docker-entrypoint.sh

CMD ["/var/www/html/docker-entrypoint.sh"]
