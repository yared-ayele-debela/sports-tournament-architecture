# Docker setup for Laravel Sports Tournament Application

## Quick Start

1. **Build and start all containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Run database migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

3. **Seed the database (optional):**
   ```bash
   docker-compose exec app php artisan db:seed
   ```

## Access Points

- **Laravel Application**: http://localhost:8000
- **PHPMyAdmin**: http://localhost:8080
- **MySQL**: localhost:3307
- **Redis**: localhost:6380

## Container Services

- **app**: Laravel 12 application with PHP 8.2 and Apache
- **mysql**: MySQL 8.0 database
- **redis**: Redis 7 for caching
- **phpmyadmin**: Web-based database administration

## Development Commands

- **View logs**: `docker-compose logs -f app`
- **Access app container**: `docker-compose exec app bash`
- **Access MySQL**: `docker-compose exec mysql mysql -u root -p`
- **Stop containers**: `docker-compose down`
- **Rebuild containers**: `docker-compose build --no-cache`

## Environment Variables

The application is configured with development settings. For production, update the environment variables in `docker-compose.yml`.

## Database Credentials

- **Host**: mysql
- **Database**: sports_tournament
- **Username**: root
- **Password**: root

## File Structure

```
monolith/
├── src/                 # Laravel application code
├── docker-compose.yml   # Docker services configuration
├── Dockerfile          # PHP/Apache container build
└── docker/
    └── mysql/
        └── my.cnf      # MySQL configuration
```
