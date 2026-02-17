# Migrate and Seed Individual Databases

## Step-by-Step Commands

### 1. Auth Service (Port 8001, Database: auth_db)

```bash
# Migrate only
docker-compose exec auth-service php artisan migrate --force

# Seed only
docker-compose exec auth-service php artisan db:seed --force

# Both together
docker-compose exec auth-service php artisan migrate --force && docker-compose exec auth-service php artisan db:seed --force
```

### 2. Tournament Service (Port 8002, Database: tournament_db)

```bash
# Migrate only
docker-compose exec tournament-service php artisan migrate --force

# Seed only
docker-compose exec tournament-service php artisan db:seed --force

# Both together
docker-compose exec tournament-service php artisan migrate --force && docker-compose exec tournament-service php artisan db:seed --force
```

### 3. Team Service (Port 8003, Database: team_db)

```bash
# Migrate only
docker-compose exec team-service php artisan migrate --force

# Seed only
docker-compose exec team-service php artisan db:seed --force

# Both together
docker-compose exec team-service php artisan migrate --force && docker-compose exec team-service php artisan db:seed --force
```

### 4. Match Service (Port 8004, Database: match_db)

```bash
# Migrate only
docker-compose exec match-service php artisan migrate --force

# Seed only
docker-compose exec match-service php artisan db:seed --force

# Both together
docker-compose exec match-service php artisan migrate --force && docker-compose exec match-service php artisan db:seed --force
```

### 5. Results Service (Port 8005, Database: results_db)

```bash
# Migrate only
docker-compose exec results-service php artisan migrate --force

# Seed only
docker-compose exec results-service php artisan db:seed --force

# Both together
docker-compose exec results-service php artisan migrate --force && docker-compose exec results-service php artisan db:seed --force
```

## Recommended Order

Due to dependencies between services, migrate and seed in this order:

1. **Auth Service** (no dependencies)
2. **Tournament Service** (depends on Auth)
3. **Team Service** (depends on Auth, Tournament)
4. **Match Service** (depends on Auth, Tournament, Team)
5. **Results Service** (depends on Auth, Match)

## Fresh Start (Drop All Tables and Re-run)

If you want to completely reset a database:

```bash
# Replace SERVICE_NAME with: auth-service, tournament-service, team-service, match-service, or results-service
docker-compose exec SERVICE_NAME php artisan migrate:fresh --seed --force
```

## Check Migration Status

To see which migrations have run for a specific service:

```bash
docker-compose exec auth-service php artisan migrate:status
docker-compose exec tournament-service php artisan migrate:status
docker-compose exec team-service php artisan migrate:status
docker-compose exec match-service php artisan migrate:status
docker-compose exec results-service php artisan migrate:status
```

## Rollback Migrations

To rollback the last batch of migrations:

```bash
docker-compose exec SERVICE_NAME php artisan migrate:rollback --force
```

To rollback all migrations:

```bash
docker-compose exec SERVICE_NAME php artisan migrate:reset --force
```

## Run Specific Seeder

To run a specific seeder class:

```bash
# Example: Run only SportSeeder in tournament-service
docker-compose exec tournament-service php artisan db:seed --class=SportSeeder --force

# Example: Run only UserSeeder in auth-service
docker-compose exec auth-service php artisan db:seed --class=UserSeeder --force
```
