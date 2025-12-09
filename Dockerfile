# ==============================================================================
# Dockerfile - Multi-Stage Build for Uptime-Kita
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Node.js Builder - Build Frontend Assets
# ------------------------------------------------------------------------------
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci

# Copy frontend source files
COPY resources/ ./resources/
COPY vite.config.ts tsconfig.json components.json tailwind.config.js ./
COPY public/ ./public/

# Build frontend assets
RUN npm run build

# ------------------------------------------------------------------------------
# Stage 2: Composer Dependencies
# ------------------------------------------------------------------------------
FROM composer:2 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev for production
ARG APP_ENV=production
RUN if [ "$APP_ENV" = "production" ]; then \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    else \
        composer install --no-scripts --no-autoloader --prefer-dist; \
    fi

# Copy application source
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# ------------------------------------------------------------------------------
# Stage 3: Production Image
# ------------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    sqlite-dev \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_sqlite \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        intl \
        pcntl \
        opcache \
        mbstring \
        xml

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www/html

# Create necessary directories
RUN mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/www/html/database \
    /var/log/supervisor \
    /run/nginx

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy Nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor/conf.d/

# Copy application from composer stage
COPY --from=composer-builder /app /var/www/html

# Copy built assets from node stage
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/database

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]

# ------------------------------------------------------------------------------
# Stage 4: Development Image (extends production)
# ------------------------------------------------------------------------------
FROM production AS development

# Install Node.js for development
RUN apk add --no-cache nodejs npm

# Install additional dev tools
RUN apk add --no-cache vim nano

# Install Xdebug for debugging
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy development PHP configuration
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/99-dev.ini

# Override entrypoint for development
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
