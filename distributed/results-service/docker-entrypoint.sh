#!/bin/bash
set -e

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

echo "Starting PHP server..."
exec php -S 0.0.0.0:8005 -t public
