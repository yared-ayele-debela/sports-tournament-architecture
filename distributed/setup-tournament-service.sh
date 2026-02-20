#!/bin/bash

# Script to setup Tournament Service
# This script automates the complete setup process for the tournament-service
# Usage: ./setup-tournament-service.sh

# Don't use set -e, we'll handle errors manually for better control

# Increase Docker Compose timeout to avoid timeout errors
export COMPOSE_HTTP_TIMEOUT=${COMPOSE_HTTP_TIMEOUT:-120}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SERVICE_NAME="tournament-service"
DB_NAME="tournament-db"
DB_USER="root"
DB_PASSWORD="rootpassword"
MAX_WAIT_TIME=60  # Maximum time to wait for database (in seconds)

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Tournament Service Setup Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to check if database is ready
wait_for_database() {
    echo -e "${YELLOW}⏳ Waiting for database to be ready...${NC}"
    local count=0
    while [ $count -lt $MAX_WAIT_TIME ]; do
        if docker-compose exec -T $DB_NAME mysqladmin ping -h localhost -u$DB_USER -p$DB_PASSWORD --silent 2>/dev/null; then
            echo -e "${GREEN}✅ Database is ready!${NC}"
            echo ""
            return 0
        fi
        echo -n "."
        sleep 1
        count=$((count + 1))
    done
    echo ""
    echo -e "${RED}❌ Error: Database did not become ready within $MAX_WAIT_TIME seconds${NC}"
    exit 1
}

# Function to fix database user permissions
fix_database_user() {
    echo -e "${YELLOW}🔧 Attempting to fix database user permissions...${NC}"
    
    # Get the service's DB credentials
    local service_db_user=$(docker-compose exec -T $SERVICE_NAME printenv DB_USERNAME 2>/dev/null | tr -d '\r\n' || echo "root")
    local service_db_pass=$(docker-compose exec -T $SERVICE_NAME printenv DB_PASSWORD 2>/dev/null | tr -d '\r\n' || echo "rootpassword")
    local service_db_name=$(docker-compose exec -T $SERVICE_NAME printenv DB_DATABASE 2>/dev/null | tr -d '\r\n' || echo "tournament_db")
    
    echo -e "${BLUE}   Service is trying to connect as: ${service_db_user}${NC}"
    
    # If using root, we're good
    if [ "$service_db_user" = "root" ]; then
        echo -e "${GREEN}✅ Using root user - permissions should be fine${NC}"
        echo ""
        return 0
    fi
    
    # If using a non-root user, grant permissions
    echo -e "${YELLOW}   Granting permissions to user '${service_db_user}'...${NC}"
    
    # Try to grant all privileges to the user
    if docker-compose exec -T $DB_NAME mysql -uroot -p$DB_PASSWORD -e \
        "GRANT ALL PRIVILEGES ON ${service_db_name}.* TO '${service_db_user}'@'%' IDENTIFIED BY '${service_db_pass}'; FLUSH PRIVILEGES;" 2>/dev/null; then
        echo -e "${GREEN}✅ Database user permissions granted${NC}"
        echo ""
        return 0
    else
        # Try alternative: update password if user exists
        if docker-compose exec -T $DB_NAME mysql -uroot -p$DB_PASSWORD -e \
            "ALTER USER '${service_db_user}'@'%' IDENTIFIED BY '${service_db_pass}'; GRANT ALL PRIVILEGES ON ${service_db_name}.* TO '${service_db_user}'@'%'; FLUSH PRIVILEGES;" 2>/dev/null; then
            echo -e "${GREEN}✅ Database user password updated and permissions granted${NC}"
            echo ""
            return 0
        else
            echo -e "${YELLOW}⚠️  Could not automatically fix permissions${NC}"
            echo -e "${YELLOW}   You may need to manually fix database credentials${NC}"
            echo ""
            return 1
        fi
    fi
}

# Function to verify database connection from service
verify_service_db_connection() {
    echo -e "${YELLOW}🔍 Verifying database connection from service...${NC}"
    # Try a simple database query to verify connection
    if docker-compose exec -T $SERVICE_NAME php artisan db:show >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Service can connect to database${NC}"
        echo ""
        return 0
    else
        # If db:show doesn't work, try migrate:status as fallback
        if docker-compose exec -T $SERVICE_NAME php artisan migrate:status >/dev/null 2>&1; then
            echo -e "${GREEN}✅ Service can connect to database${NC}"
            echo ""
            return 0
        else
            echo -e "${YELLOW}⚠️  Could not verify connection (will check during migrations)${NC}"
            echo ""
            return 0  # Don't fail here, let migrations catch the error with better message
        fi
    fi
}

# Function to run command with error handling
run_command() {
    local description=$1
    shift
    local cmd=("$@")
    
    echo -e "${YELLOW}📦 $description...${NC}"
    if "${cmd[@]}" 2>&1; then
        echo -e "${GREEN}✅ $description completed${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}❌ Error: $description failed${NC}"
        return 1
    fi
}

# Step 1: Wait for database
wait_for_database

# Step 2: Install Composer Dependencies
if ! run_command "Installing Composer dependencies" \
    docker-compose exec -T $SERVICE_NAME composer install; then
    echo -e "${YELLOW}⚠️  Composer install failed, retrying once...${NC}"
    sleep 2
    if ! run_command "Retrying Composer dependencies installation" \
        docker-compose exec -T $SERVICE_NAME composer install; then
        echo -e "${RED}❌ Fatal: Failed to install Composer dependencies after retry${NC}"
        exit 1
    fi
fi

# Step 3: Verify and fix tournament-service .env file
echo -e "${YELLOW}🔍 Checking tournament-service .env file...${NC}"
if [ -f "tournament-service/.env" ]; then
    # Check if DB_USERNAME is set correctly
    if grep -q "^DB_USERNAME=user" tournament-service/.env 2>/dev/null; then
        echo -e "${YELLOW}⚠️  Found DB_USERNAME=user in tournament-service/.env, updating to root...${NC}"
        # Update to use root user
        sed -i 's/^DB_USERNAME=user$/DB_USERNAME=root/' tournament-service/.env
        sed -i 's/^DB_PASSWORD=.*$/DB_PASSWORD=rootpassword/' tournament-service/.env
        echo -e "${GREEN}✅ Updated tournament-service/.env to use root credentials${NC}"
        echo ""
    elif grep -q "^DB_USERNAME=root" tournament-service/.env 2>/dev/null; then
        echo -e "${GREEN}✅ tournament-service/.env is configured correctly${NC}"
        echo ""
    else
        echo -e "${YELLOW}⚠️  DB_USERNAME not found in tournament-service/.env, ensuring it's set...${NC}"
        # Ensure DB_USERNAME is set
        if ! grep -q "^DB_USERNAME=" tournament-service/.env 2>/dev/null; then
            echo "DB_USERNAME=root" >> tournament-service/.env
        fi
        if ! grep -q "^DB_PASSWORD=" tournament-service/.env 2>/dev/null; then
            echo "DB_PASSWORD=rootpassword" >> tournament-service/.env
        fi
        echo -e "${GREEN}✅ Added database credentials to tournament-service/.env${NC}"
        echo ""
    fi
else
    echo -e "${YELLOW}⚠️  tournament-service/.env not found, creating from example...${NC}"
    if [ -f "tournament-service/.env.example" ]; then
        cp tournament-service/.env.example tournament-service/.env
        # Update database credentials
        sed -i 's/^DB_USERNAME=.*$/DB_USERNAME=root/' tournament-service/.env
        sed -i 's/^DB_PASSWORD=.*$/DB_PASSWORD=rootpassword/' tournament-service/.env
        sed -i 's/^DB_HOST=.*$/DB_HOST=tournament-db/' tournament-service/.env
        sed -i 's/^DB_DATABASE=.*$/DB_DATABASE=tournament_db/' tournament-service/.env
        echo -e "${GREEN}✅ Created tournament-service/.env with correct credentials${NC}"
        echo ""
    fi
fi

# Step 3.5: Ensure .env file exists in container
echo -e "${YELLOW}📝 Ensuring .env file exists in container...${NC}"
# First, try to create from .env.example if .env doesn't exist in container
docker-compose exec -T $SERVICE_NAME bash -c "if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || touch .env; fi" || true

# If .env exists on host, copy it to container (overwrite if needed)
if [ -f "tournament-service/.env" ]; then
    docker cp tournament-service/.env ${SERVICE_NAME}:/var/www/html/.env 2>/dev/null || true
    # Verify the file was copied
    if docker-compose exec -T $SERVICE_NAME test -f .env 2>/dev/null; then
        echo -e "${GREEN}✅ .env file exists in container${NC}"
    else
        echo -e "${YELLOW}⚠️  Warning: Could not verify .env file in container, will try to create it${NC}"
        docker-compose exec -T $SERVICE_NAME bash -c "cp .env.example .env 2>/dev/null || touch .env" || true
    fi
else
    echo -e "${YELLOW}⚠️  tournament-service/.env not found on host, using .env.example from container${NC}"
    docker-compose exec -T $SERVICE_NAME bash -c "if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || touch .env; fi" || true
fi
echo ""

# Step 3.6: Clear config cache to ensure new .env values are loaded
echo -e "${YELLOW}🔄 Clearing Laravel config cache...${NC}"
docker-compose exec -T $SERVICE_NAME php artisan config:clear >/dev/null 2>&1 || true
docker-compose exec -T $SERVICE_NAME php artisan cache:clear >/dev/null 2>&1 || true
echo -e "${GREEN}✅ Config cache cleared${NC}"
echo ""

# Step 4: Generate Application Key
if ! run_command "Generating application key" \
    docker-compose exec -T $SERVICE_NAME php artisan key:generate --force; then
    echo -e "${RED}❌ Fatal: Failed to generate application key${NC}"
    exit 1
fi

# Step 5: Fix database user permissions if needed
fix_database_user

# Step 5.5: Verify service can connect to database before migrations
verify_service_db_connection

# Step 6: Run Migrations
echo -e "${YELLOW}📦 Running database migrations...${NC}"
migration_output=$(docker-compose exec -T $SERVICE_NAME php artisan migrate:fresh --force 2>&1)
migration_exit_code=$?

if [ $migration_exit_code -eq 0 ]; then
    echo -e "${GREEN}✅ Running database migrations completed${NC}"
    echo ""
else
    echo -e "${RED}❌ Error: Running database migrations failed${NC}"
    echo ""
    # Check for common database connection errors
    if echo "$migration_output" | grep -qi "access denied\|1045"; then
        echo -e "${RED}❌ Database authentication failed!${NC}"
        echo ""
        echo -e "${YELLOW}The service cannot connect to the database with the current credentials.${NC}"
        echo -e "${YELLOW}Please check your .env file and ensure database credentials match:${NC}"
        echo ""
        echo -e "${BLUE}Option 1: Use root user (recommended for setup)${NC}"
        echo -e "  Add to your .env file:"
        echo -e "  ${YELLOW}DB_USER=root${NC}"
        echo -e "  ${YELLOW}DB_ROOT_PASSWORD=rootpassword${NC}"
        echo ""
        echo -e "${BLUE}Option 2: Fix the user password${NC}"
        echo -e "  If using DB_USER=user, ensure the password matches:"
        echo -e "  ${YELLOW}DB_USER=user${NC}"
        echo -e "  ${YELLOW}DB_PASSWORD=password${NC}"
        echo -e "  ${YELLOW}DB_ROOT_PASSWORD=rootpassword${NC}"
        echo ""
        echo -e "${BLUE}Option 3: Manually fix in database${NC}"
        echo -e "  Run: ${YELLOW}docker-compose exec tournament-db mysql -uroot -prootpassword${NC}"
        echo -e "  Then execute:"
        echo -e "  ${YELLOW}ALTER USER 'user'@'%' IDENTIFIED BY 'rootpassword';${NC}"
        echo -e "  ${YELLOW}GRANT ALL PRIVILEGES ON tournament_db.* TO 'user'@'%';${NC}"
        echo -e "  ${YELLOW}FLUSH PRIVILEGES;${NC}"
        echo ""
        echo -e "${YELLOW}Current service environment variables:${NC}"
        docker-compose exec -T $SERVICE_NAME printenv | grep -E "^DB_" | sort || true
        echo ""
        echo -e "${YELLOW}Database container environment variables:${NC}"
        docker-compose exec -T $DB_NAME printenv | grep -E "^MYSQL_" | sort || true
        echo ""
    fi
    echo -e "${YELLOW}Migration error output:${NC}"
    echo "$migration_output"
    echo ""
    exit 1
fi

# Step 7: Run Database Seeders
if ! run_command "Running database seeders" \
    docker-compose exec -T $SERVICE_NAME php artisan db:seed --force; then
    echo -e "${RED}❌ Fatal: Failed to run database seeders${NC}"
    exit 1
fi

# Step 8: Clear Cache
if ! run_command "Clearing application cache" \
    docker-compose exec -T $SERVICE_NAME php artisan optimize:clear; then
    echo -e "${YELLOW}⚠️  Warning: Failed to clear cache (non-critical)${NC}"
    echo ""
fi

# Final Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✅ Tournament Service setup completed successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}Service is available at:${NC}"
echo -e "  - Direct: ${BLUE}http://localhost:8002${NC}"
echo -e "  - Health Check: ${BLUE}http://localhost:8002/api/health${NC}"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo -e "  1. Verify service is running: ${YELLOW}docker-compose ps tournament-service${NC}"
echo -e "  2. Check service logs: ${YELLOW}docker-compose logs -f tournament-service${NC}"
echo -e "  3. Test health endpoint: ${YELLOW}curl http://localhost:8002/api/health${NC}"
echo -e "  4. Test tournaments endpoint: ${YELLOW}curl http://localhost:8002/api/tournaments${NC}"
echo ""
