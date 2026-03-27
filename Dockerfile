# ---- Stage 1: Composer dependencies ----
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ---- Stage 2: Node build (Vite) ----
FROM node:18-slim AS node

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 3: Production image ----
FROM php:8.2-fpm-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    gettext-base \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        intl \
        curl \
        mbstring \
        calendar \
        xml \
        dom \
        fileinfo \
        ctype \
        filter \
        tokenizer \
        zip \
        bcmath \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# OPcache production settings
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=0\n\
opcache.save_comments=1\n\
opcache.fast_shutdown=1" > /usr/local/etc/php/conf.d/opcache.ini

# PHP production settings
RUN echo "memory_limit=512M\n\
upload_max_filesize=100M\n\
post_max_size=100M\n\
max_execution_time=300\n\
expose_php=Off\n\
log_errors=On\n\
error_log=/dev/stderr" > /usr/local/etc/php/conf.d/production.ini

# Copy config files
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/nginx.conf /etc/nginx/templates/default.conf.template

# Remove default nginx site
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built assets from previous stages
COPY --from=composer /app/vendor ./vendor
COPY --from=node /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# deploy.sh is the entrypoint — it runs migrations then starts supervisord
RUN chmod +x deploy.sh

EXPOSE ${PORT:-8080}

CMD ["bash", "deploy.sh"]
