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
php artisan optimize || echo "WARNING: optimize failed"

echo "=== Starting server on port ${PORT:-8080} ==="
exec PHP_CLI_SERVER_WORKERS=8 php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
