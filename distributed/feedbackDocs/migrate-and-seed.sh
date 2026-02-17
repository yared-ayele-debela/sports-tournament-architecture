#!/bin/bash

# Script to migrate and seed all Laravel services
# Usage: ./migrate-and-seed.sh

echo "🚀 Starting migrations and seeding for all services..."
echo ""

# Wait for databases to be ready
echo "⏳ Waiting for databases to be ready..."
sleep 5

# Auth Service
echo "📦 Migrating and seeding Auth Service..."
docker-compose exec -T auth-service php artisan migrate --force 2>&1 | grep -v "already exists" || true
docker-compose exec -T auth-service php artisan db:seed --force 2>&1 | grep -v "Duplicate entry" || true
echo "✅ Auth Service done"
echo ""

# Tournament Service
echo "📦 Migrating and seeding Tournament Service..."
docker-compose exec -T tournament-service php artisan migrate --force 2>&1 | grep -v "already exists" || true
docker-compose exec -T tournament-service php artisan db:seed --force 2>&1 | grep -v "Duplicate entry" || true
echo "✅ Tournament Service done"
echo ""

# Team Service
echo "📦 Migrating and seeding Team Service..."
docker-compose exec -T team-service php artisan migrate --force
docker-compose exec -T team-service php artisan db:seed --force
echo "✅ Team Service done"
echo ""

# Match Service
echo "📦 Migrating and seeding Match Service..."
docker-compose exec -T match-service php artisan migrate --force
docker-compose exec -T match-service php artisan db:seed --force
echo "✅ Match Service done"
echo ""

# Results Service
echo "📦 Migrating and seeding Results Service..."
docker-compose exec -T results-service php artisan migrate --force
docker-compose exec -T results-service php artisan db:seed --force
echo "✅ Results Service done"
echo ""

echo "🎉 All migrations and seedings completed!"
