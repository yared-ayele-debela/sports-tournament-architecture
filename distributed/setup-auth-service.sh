#!/bin/bash

# Script to setup Auth Service
# This script automates the complete setup process for the auth-service
# Usage: ./setup-auth-service.sh

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
SERVICE_NAME="auth-service"
DB_NAME="auth-db"
DB_USER="root"
DB_PASSWORD="rootpassword"
MAX_WAIT_TIME=90  # Maximum time to wait for database (in seconds)

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Auth Service Setup Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to check if database is ready
wait_for_database() {
    echo -e "${YELLOW}Step 2.1: Waiting for database to be ready...${NC}"
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

# Function to run command with retry on network timeout
run_with_retry() {
    local max_retries=3
    local retry_count=0
    local command="$1"
    local description="$2"
    
    while [ $retry_count -lt $max_retries ]; do
        if docker-compose exec -T $SERVICE_NAME bash -c "$command" 2>/dev/null; then
            return 0
        fi
        retry_count=$((retry_count + 1))
        if [ $retry_count -lt $max_retries ]; then
            echo -e "${YELLOW}⚠️  Network timeout occurred. Retrying ($retry_count/$max_retries)...${NC}"
            sleep 2
        fi
    done
    
    echo -e "${RED}❌ Failed to execute: $description${NC}"
    return 1
}

# Step 1: Ensure .env is set up correctly on the host
echo -e "${YELLOW}Step 1: Checking auth-service .env file...${NC}"
if [ ! -f "auth-service/.env" ]; then
    if [ -f "auth-service/.env.example" ]; then
        echo -e "${YELLOW}   Creating .env from .env.example...${NC}"
        cp auth-service/.env.example auth-service/.env
    else
        echo -e "${RED}❌ No .env or .env.example found in auth-service/. Cannot continue.${NC}"
        exit 1
    fi
fi

# Ensure DB credentials in .env point to root (safe default for local dev)
sed -i 's/^DB_HOST=.*$/DB_HOST=auth-db/' auth-service/.env 2>/dev/null || true
sed -i 's/^DB_DATABASE=.*$/DB_DATABASE=auth_db/' auth-service/.env 2>/dev/null || true
sed -i 's/^DB_USERNAME=.*$/DB_USERNAME=root/' auth-service/.env 2>/dev/null || true
sed -i 's/^DB_PASSWORD=.*$/DB_PASSWORD=rootpassword/' auth-service/.env 2>/dev/null || true

# Add missing keys if not present
grep -q "^DB_HOST=" auth-service/.env      || echo "DB_HOST=auth-db"        >> auth-service/.env
grep -q "^DB_DATABASE=" auth-service/.env  || echo "DB_DATABASE=auth_db"    >> auth-service/.env
grep -q "^DB_USERNAME=" auth-service/.env  || echo "DB_USERNAME=root"       >> auth-service/.env
grep -q "^DB_PASSWORD=" auth-service/.env  || echo "DB_PASSWORD=rootpassword" >> auth-service/.env

echo -e "${GREEN}✅ auth-service/.env is configured${NC}"
echo ""

# Step 2.1: Wait for database
wait_for_database

# Step 2.2: Install Composer Dependencies
echo -e "${YELLOW}Step 2.2: Installing Composer dependencies...${NC}"
if run_with_retry "composer install --no-interaction" "Composer install"; then
    echo -e "${GREEN}✅ Composer dependencies installed${NC}"
else
    echo -e "${RED}❌ Failed to install Composer dependencies${NC}"
    exit 1
fi
echo ""

# Step 2.3: Generate Application Key
echo -e "${YELLOW}Step 2.3: Generating application key...${NC}"
if docker-compose exec -T $SERVICE_NAME php artisan key:generate --force 2>/dev/null; then
    echo -e "${GREEN}✅ Application key generated${NC}"
else
    echo -e "${YELLOW}⚠️  Key generation may have failed or key already exists${NC}"
fi
echo ""

# Step 2.4: Check if Laravel Passport is installed
echo -e "${YELLOW}Step 2.4: Checking if Laravel Passport is installed...${NC}"
if docker-compose exec -T $SERVICE_NAME composer show laravel/passport 2>/dev/null | grep -q "laravel/passport"; then
    echo -e "${GREEN}✅ Laravel Passport is already installed${NC}"
else
    echo -e "${YELLOW}   Laravel Passport not found. Installing...${NC}"
    if run_with_retry "composer require laravel/passport --no-interaction" "Install Passport"; then
        echo -e "${GREEN}✅ Laravel Passport installed${NC}"
    else
        echo -e "${RED}❌ Failed to install Laravel Passport${NC}"
        exit 1
    fi
fi
echo ""

# Step 2.5: Run Migrations
echo -e "${YELLOW}Step 2.5: Running database migrations...${NC}"
if docker-compose exec -T $SERVICE_NAME php artisan migrate:fresh --force 2>/dev/null; then
    echo -e "${GREEN}✅ Migrations completed${NC}"
else
    echo -e "${YELLOW}⚠️  Migration may have encountered 'already exists' errors (this is normal if Passport was previously installed)${NC}"
fi
echo ""

# Step 2.6: Check if Passport keys exist and install if needed
echo -e "${YELLOW}Step 2.6: Checking Passport keys...${NC}"
if docker-compose exec -T $SERVICE_NAME test -f storage/oauth-private.key 2>/dev/null; then
    echo -e "${GREEN}✅ Passport keys already exist${NC}"
else
    echo -e "${YELLOW}   Passport keys missing. Generating keys...${NC}"
    if docker-compose exec -T $SERVICE_NAME php artisan passport:install --force 2>/dev/null; then
        echo -e "${GREEN}✅ Passport keys generated${NC}"
    else
        echo -e "${RED}❌ Failed to generate Passport keys${NC}"
        exit 1
    fi
fi
echo ""

# Step 2.7: Create Personal Access Client
echo -e "${YELLOW}Step 2.7: Creating Personal Access Client...${NC}"
if docker-compose exec -T $SERVICE_NAME php artisan passport:client --personal --name="Personal Access Client" --no-interaction 2>/dev/null; then
    echo -e "${GREEN}✅ Personal Access Client created${NC}"
else
    echo -e "${YELLOW}⚠️  Personal Access Client may already exist (this is fine)${NC}"
fi
echo ""

# Step 2.8: Run Database Seeders
echo -e "${YELLOW}Step 2.8: Running database seeders...${NC}"
if docker-compose exec -T $SERVICE_NAME php artisan db:seed --force 2>/dev/null; then
    echo -e "${GREEN}✅ Database seeders completed${NC}"
else
    echo -e "${RED}❌ Failed to run database seeders${NC}"
    exit 1
fi
echo ""

# Step 2.9: Clear cache
echo -e "${YELLOW}Step 2.9: Clearing application cache...${NC}"
if docker-compose exec -T $SERVICE_NAME php artisan optimize:clear 2>/dev/null; then
    echo -e "${GREEN}✅ Cache cleared${NC}"
else
    echo -e "${YELLOW}⚠️  Cache clear may have encountered issues${NC}"
fi
echo ""

# Final Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✅ Auth Service Setup Complete!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}Service should be available at:${NC}"
echo -e "  - Direct:       ${BLUE}http://localhost:8001${NC}"
echo -e "  - Health Check: ${BLUE}http://localhost:8001/api/health${NC}"
echo ""
echo -e "${GREEN}Useful commands:${NC}"
echo -e "  Check status:  ${YELLOW}docker-compose ps${NC}"
echo -e "  View logs:     ${YELLOW}docker-compose logs -f $SERVICE_NAME${NC}"
echo -e "  Test health:   ${YELLOW}curl http://localhost:8001/api/health${NC}"
echo ""