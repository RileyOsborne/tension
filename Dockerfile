FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    sqlite \
    libzip-dev \
    zip \
    unzip \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip bcmath pcntl

# Install SQLite extension
RUN apk add --no-cache sqlite-dev && docker-php-ext-install pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------- Build stage ----------
FROM base AS build

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package files for npm caching
COPY package.json package-lock.json ./
RUN npm ci

# Copy application code
COPY . .

# Complete composer setup
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# ---------- Production stage ----------
FROM base AS production

# Copy application from build stage
COPY --from=build /var/www/html /var/www/html

# Remove dev files
RUN rm -rf node_modules tests .env.example

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Create required directories and set permissions
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/bootstrap/cache \
    /var/run/nginx \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Ensure SQLite database exists
RUN touch /var/www/html/database/database.sqlite \
    && chown www-data:www-data /var/www/html/database/database.sqlite \
    && chmod 666 /var/www/html/database/database.sqlite

EXPOSE 80 8989

ENTRYPOINT ["/entrypoint.sh"]
