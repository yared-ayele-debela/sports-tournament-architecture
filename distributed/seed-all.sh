#!/bin/bash
set -e

services=(
  auth-service
  tournament-service
  team-service
  match-service
  results-service
)

# Function to wait for container to be healthy
wait_for_container() {
  local service=$1
  local max_attempts=30
  local attempt=0
  
  echo "Waiting for $service to be ready..."
  while [ $attempt -lt $max_attempts ]; do
    if docker-compose ps "$service" | grep -q "Up"; then
      # Try to execute a simple command to check if container is responsive
      if docker-compose exec -T "$service" php artisan --version >/dev/null 2>&1; then
        echo "$service is ready!"
        return 0
      fi
    fi
    attempt=$((attempt + 1))
    sleep 2
  done
  
  echo "Warning: $service may not be fully ready, but continuing..."
  return 0
}

echo "Waiting for all services to be ready..."
for s in "${services[@]}"; do
  wait_for_container "$s"
done

echo ""
echo "Running migrations..."
for s in "${services[@]}"; do
  echo "-> $s"
  docker-compose exec "$s" php artisan migrate:fresh --force
done

echo ""
echo "Running seeders..."
for s in "${services[@]}"; do
  echo "-> $s"
  docker-compose exec "$s" php artisan db:seed --force
done

echo ""
echo "Done."