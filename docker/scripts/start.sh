#!/bin/bash

# Exit on any error
set -e

echo "Starting GaritaApp Laravel Application..."

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h"mysql" -u"garita_user" -p"garita_password" --silent --ssl-mode=DISABLED; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is ready!"

# Copy environment file
if [ ! -f /var/www/html/.env ]; then
    echo "Copying environment configuration..."
    cp /var/www/html/.env.docker /var/www/html/.env
fi

# Generate application key if not set
if grep -q "APP_KEY=base64:your-app-key-here" /var/www/html/.env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache configuration
echo "Clearing and caching configuration..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Create storage link
echo "Creating storage link..."
php artisan storage:link || true

# Set proper permissions
echo "Setting proper permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Cache configuration for production
echo "Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "GaritaApp is ready!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf