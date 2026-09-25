# ==========================================
# Stage 1: Install Composer Dependencies
# ==========================================
FROM composer:2 AS composer-builder

WORKDIR /app

# Copy composer manifests
COPY composer.json composer.lock* ./

# Install production dependencies without running artisan scripts
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    && composer clear-cache

# ==========================================
# Stage 2: Build Frontend Assets (Vite)
# ==========================================
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy package manifests
COPY package.json package-lock.json* ./

# Install npm dependencies
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Copy frontend source files and config
COPY resources resources/
COPY public public/
COPY vite.config.js tailwind.config.js postcss.config.js jsconfig.json ./

# Copy Ziggy assets from composer stage (required by resources/js/app.js)
COPY --from=composer-builder /app/vendor/tightenco/ziggy vendor/tightenco/ziggy

# Build production assets and clean npm cache
RUN npm run build && npm cache clean --force

# ==========================================
# Stage 3: Application Production Runtime
# ==========================================
FROM php:8.4-fpm-bookworm

ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies, Nginx, and Supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install official PHP extension installer and compile required extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory to standard webroot
WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy vendor dependencies from composer-builder stage
COPY --from=composer-builder /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from node-builder stage
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Finish composer autoloader optimization
RUN composer dump-autoload --optimize && composer clear-cache

# Configure Nginx, Supervisor, and PHP
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini $PHP_INI_DIR/conf.d/custom.ini

# Install entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod 755 /usr/local/bin/entrypoint.sh

# Ensure Laravel storage and bootstrap cache structure exist
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Enforce ownership and permissions: 775 for folders, 664 for files (NO 777)
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 775 {} + \
    && find /var/www/html -type f -exec chmod 664 {} + \
    && chmod 755 /usr/local/bin/entrypoint.sh

# Expose default HTTP ports (Render automatically sets $PORT, e.g. 10000)
EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
