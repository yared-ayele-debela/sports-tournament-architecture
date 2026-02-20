#!/bin/bash
set -e

# # Ensure we're in the correct directory
# cd /var/www/html || exit 1

# # Ensure public directory exists
# if [ ! -d "public" ]; then
#   echo "Error: Directory public does not exist!"
#   echo "Current directory: $(pwd)"
#   echo "Contents: $(ls -la)"
#   exit 1
# fi

echo "Waiting for database to be ready..."
until php artisan migrate:status 2>/dev/null || mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SELECT 1" 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Generate application key if not set
if [ ! -f .env ]; then
  cp .env.example .env 2>/dev/null || true
fi

php artisan key:generate --force || true

# Use SERVICE_PORT environment variable, default to 8001 if not set
PORT=${SERVICE_PORT:-8001}

echo "Starting PHP server on port $PORT..."
exec php -S 0.0.0.0:$PORT -t public
