#!/bin/bash
echo "=== Railway Deploy ==="

# Clear build-phase cache
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/events.php

# Run composer scripts skipped during build (package:discover needs DB)
composer dump-autoload --optimize 2>/dev/null || true
php artisan package:discover --ansi 2>/dev/null || true

# Check if this is a first deploy by attempting a safe artisan command
if php artisan migrate:status > /dev/null 2>&1; then
    echo "Running migrations..."
    php artisan migrate --force || echo "WARNING: migrate failed"
else
    echo "First deploy — running migrations and seeding..."
    php artisan migrate --force || { echo "ERROR: migrate failed"; exit 1; }
    php artisan db:seed --force || echo "WARNING: db:seed failed"
    php artisan b2b-suite:install || echo "WARNING: b2b-suite:install failed"
    php artisan db:seed --class="Rydeen\Core\Database\Seeders\RydeenSeeder" --force || echo "WARNING: Rydeen seed failed"
fi

php artisan storage:link --force || true
touch storage/installed

# Ensure Rydeen branding is applied
php artisan db:seed --class="Rydeen\Core\Database\Seeders\RydeenSeeder" --force || echo "WARNING: Rydeen seed failed"

php artisan optimize || echo "WARNING: optimize failed"

# Ensure errors are logged to stderr
export LOG_CHANNEL=stderr
export LOG_LEVEL=debug

echo "=== Starting Nginx + PHP-FPM on port ${PORT:-8080} ==="

# Template the Railway PORT into the Nginx config
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
