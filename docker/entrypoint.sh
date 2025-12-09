#!/bin/sh
# ==============================================================================
# Docker Entrypoint Script for Uptime-Kita
# ==============================================================================

set -e

echo "============================================="
echo "Starting Uptime-Kita Container..."
echo "============================================="

# Function to wait for a service
wait_for_service() {
    local host=$1
    local port=$2
    local timeout=${3:-30}
    local counter=0

    echo "Waiting for $host:$port..."
    while ! nc -z "$host" "$port" 2>/dev/null; do
        counter=$((counter + 1))
        if [ $counter -ge $timeout ]; then
            echo "Timeout waiting for $host:$port"
            return 1
        fi
        sleep 1
    done
    echo "$host:$port is available"
}

# Create storage directories if they don't exist
echo "Creating storage directories..."
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Create log files
touch /var/www/html/storage/logs/laravel.log
touch /var/www/html/storage/logs/scheduler.log
touch /var/www/html/storage/logs/queue-worker.log

# Set correct permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/database

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    if [ ! -f /var/www/html/.env ] || ! grep -q "^APP_KEY=" /var/www/html/.env || grep -q "^APP_KEY=$" /var/www/html/.env; then
        echo "Generating application key..."
        php /var/www/html/artisan key:generate --force --no-interaction
    fi
fi

# Run migrations (with --force for production)
echo "Running database migrations..."
php /var/www/html/artisan migrate --force --no-interaction 2>/dev/null || true

# Clear and cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing for production..."
    php /var/www/html/artisan config:cache --no-interaction 2>/dev/null || true
    php /var/www/html/artisan route:cache --no-interaction 2>/dev/null || true
    php /var/www/html/artisan view:cache --no-interaction 2>/dev/null || true
fi

# Create storage link if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage link..."
    php /var/www/html/artisan storage:link --no-interaction 2>/dev/null || true
fi

echo "============================================="
echo "Uptime-Kita Container Ready!"
echo "============================================="

# Execute the CMD
exec "$@"
