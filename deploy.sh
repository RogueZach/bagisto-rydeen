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

# Write the complete Nginx config with the correct PORT
cat > /etc/nginx/nginx.conf <<NGINX_EOF
worker_processes auto;
pid /run/nginx.pid;
error_log /dev/stderr warn;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    sendfile on;
    keepalive_timeout 65;
    access_log /dev/stdout;

    server {
        listen ${PORT:-8080};
        server_name _;
        root /var/www/html/public;
        index index.php;

        client_max_body_size 100M;

        location = /healthz {
            access_log off;
            return 200 'ok';
            add_header Content-Type text/plain;
        }

        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 30d;
            add_header Cache-Control "public, immutable";
            try_files \$uri =404;
        }

        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_read_timeout 300;
        }

        location ~ /\.ht {
            deny all;
        }
    }
}
NGINX_EOF

echo "Nginx config written. Testing..."
nginx -t 2>&1 || { echo "ERROR: Nginx config test failed"; exit 1; }
echo "Nginx config OK"

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
