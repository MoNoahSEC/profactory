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
         /var/www/html/bootstrap/cache

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Run migrations and cache configs
php artisan storage:link --force || true
php artisan migrate --force || true
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Default port
PORT=${PORT:-10000}

echo "Starting ProFactory Production Server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
