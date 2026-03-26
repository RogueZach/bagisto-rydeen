#!/bin/bash
set -e
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
php artisan migrate:status > /dev/null 2>&1
NEEDS_SEED=$?

if [ $NEEDS_SEED -ne 0 ]; then
    echo "First deploy — running migrations and seeding..."
    php artisan migrate --force
    php artisan db:seed --force
    php artisan b2b-suite:install
    php artisan db:seed --class="Rydeen\Core\Database\Seeders\RydeenSeeder" --force
else
    echo "Running migrations..."
    php artisan migrate --force
fi

php artisan storage:link --force
touch storage/installed
php artisan optimize

echo "=== Starting server on port ${PORT:-8080} ==="
PHP_CLI_SERVER_WORKERS=8 php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
