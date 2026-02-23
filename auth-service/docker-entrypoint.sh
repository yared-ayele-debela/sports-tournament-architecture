#!/bin/bash
set -e

echo "Waiting for database to be ready..."
until php artisan migrate:status 2>/dev/null || mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SELECT 1" 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Ensure .env file exists
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    cp .env.example .env
  else
    # Create minimal .env file if .env.example doesn't exist
    cat > .env <<EOF
APP_NAME=Laravel
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-auth-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-auth_db}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-rootpassword}

CACHE_DRIVER=${CACHE_STORE:-redis}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-redis}

REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PASSWORD=${REDIS_PASSWORD:-null}
REDIS_PORT=${REDIS_PORT:-6379}
EOF
  fi
fi

php artisan key:generate --force || true

# Use SERVICE_PORT environment variable, default to 8001 if not set
PORT=${SERVICE_PORT:-8001}

echo "Starting PHP server on port $PORT..."
exec php -S 0.0.0.0:$PORT -t public
