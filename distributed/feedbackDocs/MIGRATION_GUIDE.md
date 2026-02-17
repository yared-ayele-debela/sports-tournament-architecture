# Migration and Seeding Guide

## Quick Start

Run the migration and seeding script:

```bash
./migrate-and-seed.sh
```

## Manual Commands

### Individual Service Migration

#### Auth Service (Port 8001)
```bash
docker-compose exec auth-service php artisan migrate --force
docker-compose exec auth-service php artisan db:seed --force
```

#### Tournament Service (Port 8002)
```bash
docker-compose exec tournament-service php artisan migrate --force
docker-compose exec tournament-service php artisan db:seed --force
```

#### Team Service (Port 8003)
```bash
docker-compose exec team-service php artisan migrate --force
docker-compose exec team-service php artisan db:seed --force
```

#### Match Service (Port 8004)
```bash
docker-compose exec match-service php artisan migrate --force
docker-compose exec match-service php artisan db:seed --force
```

#### Results Service (Port 8005)
```bash
docker-compose exec results-service php artisan migrate --force
docker-compose exec results-service php artisan db:seed --force
```

## Fresh Migration (Drop All Tables)

If you want to start completely fresh:

```bash
# Replace SERVICE_NAME with: auth-service, tournament-service, team-service, match-service, or results-service
docker-compose exec SERVICE_NAME php artisan migrate:fresh --seed --force
```

## Check Migration Status

To see which migrations have run:

```bash
docker-compose exec auth-service php artisan migrate:status
docker-compose exec tournament-service php artisan migrate:status
docker-compose exec team-service php artisan migrate:status
docker-compose exec match-service php artisan migrate:status
docker-compose exec results-service php artisan migrate:status
```

## Issues Fixed

### 1. Database Connection Issues
- **Problem**: Team, Match, and Results services were trying to connect to `127.0.0.1` instead of Docker service names
- **Solution**: Updated `docker-compose.yml` to use explicit `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` environment variables instead of `DATABASE_URL`

### 2. Duplicate Migrations
- **Problem**: Duplicate migration files causing "table already exists" errors
- **Fixed**:
  - Removed `auth-service/database/migrations/2026_01_24_135821_create_oauth_auth_codes_table.php` (duplicate of Laravel Passport migration)
  - Removed `auth-service/database/migrations/2026_01_24_135822_create_oauth_access_tokens_table.php` (duplicate of Laravel Passport migration)
  - Removed `tournament-service/database/migrations/2026_01_31_152118_create_failed_jobs_table.php` (already created by Laravel's default `0001_01_01_000002_create_jobs_table.php`)
  - Removed `match-service/database/migrations/2026_01_20_220247_create_oauth_auth_codes_table.php` (duplicate)

### 3. Factory Faker Issues
- **Problem**: `$this->faker` was null in UserFactory for match-service and results-service
- **Solution**: Changed to use Laravel's `fake()` helper function which works even when Faker package is not installed (Laravel 10+ built-in)

## Notes

- Migrations and seeders run automatically when containers start (configured in Dockerfiles)
- The `--force` flag is required when running migrations in non-interactive environments (Docker)
- Some seeders may show "Duplicate entry" warnings if data already exists - this is normal and can be ignored
- If you see connection errors, ensure all services are running: `docker-compose ps`
