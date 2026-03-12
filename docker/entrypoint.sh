#!/bin/sh
set -e

echo "==> [TDN-Asset] Starting container setup..."

# Copy .env if not exists
if [ ! -f /var/www/.env ]; then
    echo "==> [TDN-Asset] .env not found, creating from environment variables..."
    cp /var/www/.env.example /var/www/.env
fi

# Generate APP_KEY if not set
if grep -q "^APP_KEY=$" /var/www/.env 2>/dev/null || [ -z "$(grep '^APP_KEY=' /var/www/.env | cut -d= -f2)" ]; then
    echo "==> [TDN-Asset] Generating APP_KEY..."
    php artisan key:generate --force
fi

# Clear and cache configs
echo "==> [TDN-Asset] Caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "==> [TDN-Asset] Running database migrations..."
php artisan migrate --force

# Create storage symlink if not exists
echo "==> [TDN-Asset] Linking storage..."
php artisan storage:link || true

# Fix permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "==> [TDN-Asset] Setup complete. Starting PHP-FPM..."
exec "$@"
