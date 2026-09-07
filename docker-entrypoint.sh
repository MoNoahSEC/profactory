#!/bin/sh
set -e

# Ensure SQLite database exists
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi

# Ensure storage subdirectories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/backups \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache \
         /var/www/html/database

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Ensure default APP_KEY if not provided by environment
if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:ld6xo8H/AACNFTL+N88N1ilIDzubxGvJtK4MSCDL//U="
fi

# Run migrations, seeds, and clear caches
php artisan storage:link --force || true
php artisan migrate --force || true
php artisan db:seed --force || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Dynamic port for Railway/Render/Koyeb
PORT=${PORT:-10000}

echo "Starting ProFactory Server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
