# Docker Run Guide

This guide explains how to build, run, and verify all services are working correctly.

## 🚀 Quick Start

### 1. Build and Start All Services

```bash
# Navigate to the project directory
cd /home/yared/Documents/StudyMaterials/SOFTWARE_ARCHITECTURES/sports-tournament-architecture/distributed

# Build and start all services in detached mode
docker-compose up -d --build
```

The `--build` flag ensures Docker rebuilds images with the latest changes.

### 2. Check Container Status

```bash
# Check if all containers are running
docker-compose ps

# Or use Docker directly
docker ps
```

You should see all services with status "Up":
- auth-service (port 8001)
- tournament-service (port 8002)
- team-service (port 8003)
- match-service (port 8004)
- results-service (port 8005)
- admin-dashboard (port 3000)
- public-view (port 3001)
- All database containers
- redis
- phpmyadmin (port 8082)

### 3. View Logs

```bash
# View logs for all services
docker-compose logs -f

# View logs for a specific service
docker-compose logs -f auth-service
docker-compose logs -f tournament-service
docker-compose logs -f team-service
docker-compose logs -f match-service
docker-compose logs -f results-service

# View last 100 lines of logs
docker-compose logs --tail=100 auth-service
```

**What to look for in logs:**
- ✅ "Database is ready!"
- ✅ "Running migrations..."
- ✅ "Running database seeders..."
- ✅ "Starting PHP server..."
- ✅ For auth-service: "Installing Passport..."

### 4. Test Health Endpoints

Each service has a health check endpoint. Test them:

```bash
# Auth Service
curl http://localhost:8001/api/health

# Tournament Service
curl http://localhost:8002/api/health

# Team Service
curl http://localhost:8003/api/health

# Match Service
curl http://localhost:8004/api/health

# Results Service
curl http://localhost:8005/api/health
```

Expected response:
```json
{
  "status": "ok",
  "service": "auth-service",
  "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### 5. Test Service Endpoints

```bash
# Test Auth Service - Register endpoint
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# Test Tournament Service - List tournaments
curl http://localhost:8002/api/tournaments

# Test Team Service - Health check
curl http://localhost:8003/api/health

# Test Match Service - List matches
curl http://localhost:8004/api/public/matches

# Test Results Service - Health check
curl http://localhost:8005/api/health
```

### 6. Access Frontend Applications

- **Admin Dashboard**: http://localhost:3000
- **Public View**: http://localhost:3001
- **phpMyAdmin**: http://localhost:8082

## 🔍 Detailed Verification Steps

### Check Database Connections

```bash
# Check if databases are accessible
docker-compose exec auth-service php artisan migrate:status
docker-compose exec tournament-service php artisan migrate:status
docker-compose exec team-service php artisan migrate:status
docker-compose exec match-service php artisan migrate:status
docker-compose exec results-service php artisan migrate:status
```

### Check if Migrations Ran

```bash
# Check migration status for each service
docker-compose exec auth-service php artisan migrate:status
docker-compose exec tournament-service php artisan migrate:status
docker-compose exec team-service php artisan migrate:status
docker-compose exec match-service php artisan migrate:status
docker-compose exec results-service php artisan migrate:status
```

### Check if Seeders Ran

```bash
# Check if seeders created data (example for auth-service)
docker-compose exec auth-db mysql -uroot -prootpassword auth_db -e "SELECT COUNT(*) as user_count FROM users;"
docker-compose exec tournament-db mysql -uroot -prootpassword tournament_db -e "SELECT COUNT(*) as tournament_count FROM tournaments;"
```

### Check Redis Connection

```bash
# Test Redis connection
docker-compose exec redis redis-cli ping
# Should return: PONG
```

## 🛠️ Common Commands

### Stop All Services

```bash
docker-compose down
```

### Stop and Remove Volumes (⚠️ This deletes database data)

```bash
docker-compose down -v
```

### Restart a Specific Service

```bash
docker-compose restart auth-service
```

### Rebuild a Specific Service

```bash
docker-compose up -d --build auth-service
```

### View Real-time Logs

```bash
# Follow logs for all services
docker-compose logs -f

# Follow logs for specific service
docker-compose logs -f auth-service
```

### Execute Commands in Containers

```bash
# Access shell in a service container
docker-compose exec auth-service bash

# Run artisan commands
docker-compose exec auth-service php artisan migrate
docker-compose exec auth-service php artisan db:seed
```

## 🐛 Troubleshooting

### Services Not Starting

1. **Check logs:**
   ```bash
   docker-compose logs auth-service
   ```

2. **Check if database is ready:**
   ```bash
   docker-compose ps
   # All database containers should be "Up"
   ```

3. **Rebuild containers:**
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

### Database Connection Errors

1. **Wait for databases to be ready:**
   ```bash
   # Check database logs
   docker-compose logs auth-db
   ```

2. **Verify database credentials match in docker-compose.yml**

### Migration Errors

1. **Check if migrations already ran:**
   ```bash
   docker-compose exec auth-service php artisan migrate:status
   ```

2. **Manually run migrations if needed:**
   ```bash
   docker-compose exec auth-service php artisan migrate --force
   ```

### Port Already in Use

If you get "port already in use" errors:

```bash
# Find what's using the port
sudo lsof -i :8001

# Or stop existing containers
docker-compose down
```

### Clean Start (Fresh Build)

```bash
# Stop and remove all containers, networks, and volumes
docker-compose down -v

# Remove all images
docker-compose rm -f

# Rebuild everything from scratch
docker-compose up -d --build
```

## ✅ Success Indicators

Your setup is working correctly if:

1. ✅ All containers show "Up" status in `docker-compose ps`
2. ✅ Health endpoints return 200 OK status
3. ✅ Logs show "Database is ready!", "Running migrations...", "Running database seeders..."
4. ✅ No error messages in logs
5. ✅ Frontend applications are accessible
6. ✅ You can make API requests to services

## 📝 Quick Test Script

Save this as `test-services.sh`:

```bash
#!/bin/bash

echo "Testing all services..."

services=(
  "http://localhost:8001/api/health"
  "http://localhost:8002/api/health"
  "http://localhost:8003/api/health"
  "http://localhost:8004/api/health"
  "http://localhost:8005/api/health"
)

for url in "${services[@]}"; do
  echo "Testing $url..."
  response=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  if [ "$response" = "200" ]; then
    echo "✅ $url is working (HTTP $response)"
  else
    echo "❌ $url returned HTTP $response"
  fi
done

echo "Done!"
```

Make it executable and run:
```bash
chmod +x test-services.sh
./test-services.sh
```
