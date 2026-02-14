# Manual Service Setup Guide

This guide provides step-by-step instructions for manually setting up each service in the Sports Tournament Architecture.

## Prerequisites

- Docker and Docker Compose installed
- All services code is in place
- Network connectivity for Composer package downloads

## Service Setup Order

Services must be set up in this order due to dependencies:
1. **Auth Service** (no dependencies)
2. **Tournament Service** (no dependencies)
3. **Team Service** (depends on Tournament Service)
4. **Match Service** (no dependencies)
5. **Results Service** (depends on Match, Tournament, and Team Services)

---

## Step 1: Start All Services

First, build and start all services:

```bash
docker-compose up -d --build
```

Wait for all containers to be running. Check status:

```bash
docker-compose ps
```

---

## Step 2: Setup Auth Service

### 2.1 Wait for Database

Wait for the auth database to be ready:

```bash
# Check if database is ready (repeat until it responds)
docker-compose exec auth-db mysqladmin ping -h localhost -uroot -prootpassword --silent
```

### 2.2 Install Composer Dependencies

```bash
docker-compose exec auth-service composer install --no-interaction
```

If network timeout occurs, retry the command.

### 2.3 Generate Application Key

```bash
docker-compose exec auth-service php artisan key:generate --force
```

### 2.4 Install Laravel Passport (if not already installed)

Check if Passport is installed:

```bash
docker-compose exec auth-service composer show laravel/passport
```

If not installed, install it:

```bash
docker-compose exec auth-service composer require laravel/passport --no-interaction
```

### 2.5 Run Migrations

```bash
docker-compose exec auth-service php artisan migrate --force
```

Note: If you see "already exists" errors for Passport tables, this is normal if Passport was previously installed.

### 2.6 Install Passport Keys

Check if keys exist:

```bash
docker-compose exec auth-service test -f storage/oauth-private.key && echo "Keys exist" || echo "Keys missing"
```

If keys don't exist, generate them:

```bash
docker-compose exec auth-service php artisan passport:install --force
```

### 2.7 Create Personal Access Client

```bash
docker-compose exec auth-service php artisan passport:client --personal --name="Personal Access Client" --no-interaction
```

Note: If client already exists, you'll see an error - this is fine.

### 2.8 Run Database Seeders

```bash
docker-compose exec auth-service php artisan db:seed --force
```

---

## Step 3: Setup Tournament Service

### 3.1 Wait for Database

```bash
docker-compose exec tournament-db mysqladmin ping -h localhost -uroot -prootpassword --silent
```

### 3.2 Install Composer Dependencies

```bash
docker-compose exec tournament-service composer install --no-interaction
```

### 3.3 Generate Application Key

```bash
docker-compose exec tournament-service php artisan key:generate --force
```

### 3.4 Run Migrations

```bash
docker-compose exec tournament-service php artisan migrate:fresh --force
```

### 3.5 Run Database Seeders

```bash
docker-compose exec tournament-service php artisan db:seed --force
```

### 3.6 Verify Service is Running

Check if the service is responding:

```bash
curl http://localhost:8002/api/health
```

Or check tournaments endpoint:

```bash
curl http://localhost:8002/api/tournaments
```

---

## Step 4: Setup Team Service

**Important:** Tournament Service must be running and seeded before proceeding.

### 4.1 Wait for Database

```bash
docker-compose exec team-db mysqladmin ping -h localhost -uroot -prootpassword --silent
```

### 4.2 Wait for Tournament Service

Verify tournament service is ready:

```bash
# Check health endpoint
curl http://localhost:8002/api/health

# Verify tournaments API is working
curl http://localhost:8002/api/tournaments
```

### 4.3 Install Composer Dependencies

```bash
docker-compose exec team-service composer install --no-interaction
```

### 4.4 Generate Application Key

```bash
docker-compose exec team-service php artisan key:generate --force
```

### 4.5 Run Migrations

```bash
docker-compose exec team-service php artisan migrate:fresh --force
```

### 4.6 Run Database Seeders

```bash
docker-compose exec team-service php artisan db:seed --force
```

Note: This will fetch tournaments from Tournament Service. If you see errors about no tournaments, make sure Tournament Service is seeded first.

---

## Step 5: Setup Match Service

### 5.1 Wait for Database

```bash
docker-compose exec match-db mysqladmin ping -h localhost -uroot -prootpassword --silent
```

### 5.2 Install Composer Dependencies

```bash
docker-compose exec match-service composer install --no-interaction
```

### 5.3 Generate Application Key

```bash
docker-compose exec match-service php artisan key:generate --force
```

### 5.4 Run Migrations

```bash
docker-compose exec match-service php artisan migrate --force
```

### 5.5 Run Database Seeders

```bash
docker-compose exec match-service php artisan db:seed --force
```

---

## Step 6: Setup Results Service

**Important:** Match Service, Tournament Service, and Team Service must be running and seeded before proceeding.

### 6.1 Wait for Database

```bash
docker-compose exec results-db mysqladmin ping -h localhost -uroot -prootpassword --silent
```

### 6.2 Wait for Dependent Services

Verify all dependent services are ready:

```bash
# Check Match Service
curl http://localhost:8004/api/health

# Check Tournament Service
curl http://localhost:8002/api/health
curl http://localhost:8002/api/tournaments

# Check Team Service
curl http://localhost:8003/api/health
```

### 6.3 Install Composer Dependencies

```bash
docker-compose exec results-service composer install --no-interaction
```

### 6.4 Generate Application Key

```bash
docker-compose exec results-service php artisan key:generate --force
```

### 6.5 Run Migrations

```bash
docker-compose exec results-service php artisan migrate --force
```

### 6.6 Run Database Seeders

```bash
docker-compose exec results-service php artisan db:seed --force
```

Note: This will fetch tournaments from Tournament Service and matches from Match Service. If you see warnings about no tournaments or matches, this is normal if data doesn't exist yet.

---

## Quick Reference Commands

### Check Service Status

```bash
docker-compose ps
```

### View Service Logs

```bash
# View logs for a specific service
docker-compose logs -f auth-service
docker-compose logs -f tournament-service
docker-compose logs -f team-service
docker-compose logs -f match-service
docker-compose logs -f results-service
```

### Restart a Service

```bash
docker-compose restart auth-service
```

### Stop All Services

```bash
docker-compose down
```

### Stop and Remove Volumes (Clean Slate)

```bash
docker-compose down -v
```

### Rebuild a Specific Service

```bash
docker-compose up -d --build auth-service
```

### Access Service Container Shell

```bash
docker-compose exec auth-service bash
docker-compose exec tournament-service bash
```

### Run Artisan Commands

```bash
# Example: Clear cache
docker-compose exec auth-service php artisan cache:clear

# Example: Run migrations
docker-compose exec auth-service php artisan migrate

# Example: Run seeders
docker-compose exec auth-service php artisan db:seed
```

---

## Service URLs

Once all services are set up, they will be available at:

- **Auth Service**: http://localhost:8001
- **Tournament Service**: http://localhost:8002
- **Team Service**: http://localhost:8003
- **Match Service**: http://localhost:8004
- **Results Service**: http://localhost:8005
- **phpMyAdmin**: http://localhost:8082

---

## Troubleshooting

### Network Issues

If you see network errors like "needs to be recreated":

```bash
# Stop all services
docker-compose down

# Remove the network
docker network rm distributed_tournament-network

# Start services again (network will be recreated)
docker-compose up -d
```

### Composer Timeout Issues

If Composer install times out:

1. Retry the command
2. Check network connectivity: `docker-compose exec auth-service ping -c 3 8.8.8.8`
3. Try with longer timeout or offline mode

### Database Connection Issues

If database is not ready:

1. Check if container is running: `docker-compose ps auth-db`
2. Check database logs: `docker-compose logs auth-db`
3. Wait longer and retry the ping command

### Migration Errors

If you see "table already exists" errors:

- This is normal if migrations were run before
- The script uses `migrate` (not `migrate:fresh`) to preserve existing data
- If you need to reset, use: `docker-compose exec auth-service php artisan migrate:fresh --force`

### Service Dependency Issues

If a service fails because another service is not ready:

1. Check the dependent service is running: `docker-compose ps`
2. Check the dependent service health: `curl http://localhost:PORT/api/health`
3. Check the dependent service logs: `docker-compose logs -f SERVICE_NAME`
4. Make sure dependent service is seeded before proceeding

---

## Complete Setup Checklist

- [ ] All services started with `docker-compose up -d --build`
- [ ] Auth Service: Composer installed, key generated, Passport installed, migrations run, seeders run
- [ ] Tournament Service: Composer installed, key generated, migrations run, seeders run
- [ ] Team Service: Composer installed, key generated, migrations run, seeders run (after Tournament Service)
- [ ] Match Service: Composer installed, key generated, migrations run, seeders run
- [ ] Results Service: Composer installed, key generated, migrations run, seeders run (after all other services)
- [ ] All services responding to health checks
- [ ] Can access phpMyAdmin at http://localhost:8082

---

## Notes

- All services use volumes for real-time code updates
- Database passwords are set in `docker-compose.yml`
- Services communicate via Docker network (service names as hostnames)
- Redis is used for caching and queues
- Each service has its own database
