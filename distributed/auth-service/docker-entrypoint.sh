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

echo "Checking Passport keys..."
if [ -f storage/oauth-private.key ]; then
  echo "Passport keys already exist"
else
  echo "Generating Passport keys..."
  # This will run Passport migrations and generate keys / default clients if needed
  php artisan passport:install --force || echo "Warning: Failed to run passport:install, continuing..."
fi

echo "Ensuring Personal Access Client exists..."
output=$(php artisan passport:client --personal --name="Personal Access Client" --no-interaction 2>&1 || true)
if echo "$output" | grep -q "already exists"; then
  echo "Personal Access Client already exists"
elif echo "$output" | grep -q "Personal access client created"; then
  echo "Personal Access Client created successfully"
else
  echo "Warning: Personal Access Client may already exist or creation had issues"
  echo "Output: $output"
fi

echo "Starting PHP server..."
exec php -S 0.0.0.0:8001 -t public
