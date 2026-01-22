#!/bin/bash

# Team Service Setup and Test Script
# This script sets up the team-service environment and runs tests

set -e

echo "🏆 Team Service Setup and Test Script"
echo "====================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Please run this script from the team-service root directory"
    exit 1
fi

print_status "Setting up Team Service environment..."

# Check PHP version
print_status "Checking PHP version..."
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed"
    exit 1
fi

PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '-' -f 1)
print_success "PHP version: $PHP_VERSION"

# Check Composer
print_status "Checking Composer..."
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed"
    exit 1
fi

COMPOSER_VERSION=$(composer --version | cut -d ' ' -f 3)
print_success "Composer version: $COMPOSER_VERSION"

# Install dependencies
print_status "Installing PHP dependencies..."
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    composer update --no-interaction --prefer-dist --optimize-autoloader
fi

# Check .env file
print_status "Checking environment configuration..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        print_success "Created .env file from .env.example"
        print_warning "Please update .env file with your database configuration"
    else
        print_error ".env.example file not found"
        exit 1
    fi
else
    print_success ".env file exists"
fi

# Generate application key
print_status "Generating application key..."
php artisan key:generate --force

# Create storage links
print_status "Creating storage links..."
php artisan storage:link

# Check database connection
print_status "Checking database configuration..."
php artisan config:cache

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force

# Seed database (optional)
print_status "Seeding database..."
php artisan db:seed --force || print_warning "Database seeding failed or not available"

# Clear caches
print_status "Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Check service dependencies
print_status "Checking service dependencies..."

# Check auth service
AUTH_SERVICE_URL=${AUTH_SERVICE_URL:-http://localhost:8001}
print_status "Checking Auth Service at $AUTH_SERVICE_URL..."
if curl -s "$AUTH_SERVICE_URL/api/health" > /dev/null; then
    print_success "Auth Service is running"
else
    print_warning "Auth Service is not running at $AUTH_SERVICE_URL"
    print_warning "Team Service may not function correctly without Auth Service"
fi

# Check tournament service
TOURNAMENT_SERVICE_URL=${TOURNAMENT_SERVICE_URL:-http://localhost:8002}
print_status "Checking Tournament Service at $TOURNAMENT_SERVICE_URL..."
if curl -s "$TOURNAMENT_SERVICE_URL/api/health" > /dev/null; then
    print_success "Tournament Service is running"
else
    print_warning "Tournament Service is not running at $TOURNAMENT_SERVICE_URL"
    print_warning "Team Service may not function correctly without Tournament Service"
fi

# Start the service
print_status "Starting Team Service..."
SERVICE_PORT=${TEAM_SERVICE_PORT:-8004}

# Check if port is available
if lsof -Pi :$SERVICE_PORT -sTCP:LISTEN -t >/dev/null ; then
    print_warning "Port $SERVICE_PORT is already in use"
    print_status "Trying to stop existing process..."
    pkill -f "php artisan serve --port=$SERVICE_PORT" || true
    sleep 2
fi

print_status "Starting Team Service on port $SERVICE_PORT..."
php artisan serve --host=0.0.0.0 --port=$SERVICE_PORT &
SERVICE_PID=$!

# Wait for service to start
print_status "Waiting for service to start..."
sleep 5

# Check if service is running
if curl -s "http://localhost:$SERVICE_PORT/api/health" > /dev/null; then
    print_success "Team Service is running on http://localhost:$SERVICE_PORT"
else
    print_error "Team Service failed to start"
    kill $SERVICE_PID 2>/dev/null || true
    exit 1
fi

# Test basic endpoints
print_status "Testing basic endpoints..."

# Test health endpoint
print_status "Testing health endpoint..."
HEALTH_RESPONSE=$(curl -s "http://localhost:$SERVICE_PORT/api/health")
if echo "$HEALTH_RESPONSE" | grep -q "success"; then
    print_success "Health endpoint working"
else
    print_warning "Health endpoint not responding as expected"
fi

# Test public teams endpoint
print_status "Testing public teams endpoint..."
TEAMS_RESPONSE=$(curl -s "http://localhost:$SERVICE_PORT/api/public/tournaments/1/teams")
if echo "$TEAMS_RESPONSE" | grep -q "data"; then
    print_success "Public teams endpoint working"
else
    print_warning "Public teams endpoint not responding as expected"
fi

# Print service information
echo ""
print_success "Team Service Setup Complete!"
echo "=================================="
echo "Service URL: http://localhost:$SERVICE_PORT"
echo "API Documentation: http://localhost:$SERVICE_PORT/api/documentation"
echo "Health Check: http://localhost:$SERVICE_PORT/api/health"
echo ""
echo "Service PID: $SERVICE_PID"
echo ""

# Instructions for Postman testing
echo "📋 Postman Testing Instructions:"
echo "================================="
echo "1. Import the Postman collection:"
echo "   File: team-service-postman-collection.json"
echo ""
echo "2. Set up environment variables:"
echo "   - base_url: http://localhost:$SERVICE_PORT"
echo "   - auth_token: [Get from auth-service]"
echo "   - team_id: 1"
echo "   - player_id: 1"
echo "   - tournament_id: 1"
echo ""
echo "3. Get auth token:"
echo "   curl -X POST http://localhost:8001/api/auth/login \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'"
echo ""
echo "4. Run the Postman collection"
echo ""

# Instructions for cURL testing
echo "🔧 cURL Testing Instructions:"
echo "=============================="
echo "1. Get auth token:"
echo "   TOKEN=\$(curl -s -X POST http://localhost:8001/api/auth/login \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}' \\"
echo "     | jq -r '.data.access_token')"
echo ""
echo "2. Test health endpoint:"
echo "   curl http://localhost:$SERVICE_PORT/api/health"
echo ""
echo "3. Test public teams:"
echo "   curl http://localhost:$SERVICE_PORT/api/public/tournaments/1/teams"
echo ""
echo "4. Create a team (with token):"
echo "   curl -X POST http://localhost:$SERVICE_PORT/api/teams \\"
echo "     -H 'Authorization: Bearer \$TOKEN' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"tournament_id\":1,\"name\":\"Test Team\",\"coach_id\":1}'"
echo ""
echo "5. Run the test script:"
echo "   ./test-team-api.sh"
echo ""

# Instructions for stopping the service
echo "🛑 To stop the service:"
echo "========================="
echo "kill $SERVICE_PID"
echo "or"
echo "pkill -f 'php artisan serve --port=$SERVICE_PORT'"
echo ""

# Instructions for monitoring
echo "📊 Monitoring:"
echo "==============="
echo "Service logs: tail -f storage/logs/laravel.log"
echo "Process status: ps aux | grep 'php artisan serve'"
echo "Port status: lsof -i :$SERVICE_PORT"
echo ""

print_success "Team Service is ready for testing!"

# Create a test script
cat > test-team-api.sh << 'EOF'
#!/bin/bash

# Team Service API Test Script
# This script tests all team-service endpoints

set -e

# Configuration
BASE_URL=${TEAM_SERVICE_URL:-http://localhost:8004}
AUTH_URL=${AUTH_SERVICE_URL:-http://localhost:8001}

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_test() {
    echo -e "${YELLOW}[TEST]${NC} $1"
}

print_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

print_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
}

# Get auth token
print_test "Getting authentication token..."
TOKEN=$(curl -s -X POST "$AUTH_URL/api/auth/login" \
    -H 'Content-Type: application/json' \
    -d '{"email":"admin@example.com","password":"password"}' \
    | jq -r '.data.access_token')

if [ "$TOKEN" != "null" ] && [ ! -z "$TOKEN" ]; then
    print_pass "Authentication successful"
else
    print_fail "Authentication failed"
    exit 1
fi

# Test health endpoint
print_test "Testing health endpoint..."
RESPONSE=$(curl -s "$BASE_URL/api/health")
if echo "$RESPONSE" | jq -e '.success' > /dev/null 2>&1; then
    print_pass "Health check passed"
else
    print_fail "Health check failed"
fi

# Test public teams endpoint
print_test "Testing public teams endpoint..."
RESPONSE=$(curl -s "$BASE_URL/api/public/tournaments/1/teams")
if echo "$RESPONSE" | jq -e '.data' > /dev/null 2>&1; then
    print_pass "Public teams endpoint working"
else
    print_fail "Public teams endpoint failed"
fi

# Test create team
print_test "Creating a new team..."
TEAM_RESPONSE=$(curl -s -X POST "$BASE_URL/api/teams" \
    -H "Authorization: Bearer $TOKEN" \
    -H 'Content-Type: application/json' \
    -d '{"tournament_id":1,"name":"Test Team '$(date +%s)'","coach_id":1}')

TEAM_ID=$(echo "$TEAM_RESPONSE" | jq -r '.data.id // empty')

if [ ! -z "$TEAM_ID" ] && [ "$TEAM_ID" != "null" ]; then
    print_pass "Team created successfully (ID: $TEAM_ID)"
else
    print_fail "Team creation failed"
    echo "$TEAM_RESPONSE"
fi

# Test get team details
if [ ! -z "$TEAM_ID" ] && [ "$TEAM_ID" != "null" ]; then
    print_test "Getting team details..."
    RESPONSE=$(curl -s "$BASE_URL/api/teams/$TEAM_ID" \
        -H "Authorization: Bearer $TOKEN")
    
    if echo "$RESPONSE" | jq -e '.data.id' > /dev/null 2>&1; then
        print_pass "Team details retrieved successfully"
    else
        print_fail "Failed to get team details"
    fi
fi

# Test create player
if [ ! -z "$TEAM_ID" ] && [ "$TEAM_ID" != "null" ]; then
    print_test "Creating a new player..."
    PLAYER_RESPONSE=$(curl -s -X POST "$BASE_URL/api/players" \
        -H "Authorization: Bearer $TOKEN" \
        -H 'Content-Type: application/json' \
        -d "{\"team_id\":$TEAM_ID,\"full_name\":\"Test Player $(date +%s)\",\"position\":\"Forward\",\"jersey_number\":10}")
    
    PLAYER_ID=$(echo "$PLAYER_RESPONSE" | jq -r '.data.id // empty')
    
    if [ ! -z "$PLAYER_ID" ] && [ "$PLAYER_ID" != "null" ]; then
        print_pass "Player created successfully (ID: $PLAYER_ID)"
    else
        print_fail "Player creation failed"
        echo "$PLAYER_RESPONSE"
    fi
fi

# Test list players
print_test "Listing players..."
RESPONSE=$(curl -s "$BASE_URL/api/players?per_page=5" \
    -H "Authorization: Bearer $TOKEN")

if echo "$RESPONSE" | jq -e '.data' > /dev/null 2>&1; then
    print_pass "Players list retrieved successfully"
else
    print_fail "Failed to list players"
fi

# Test error scenarios
print_test "Testing unauthorized access..."
RESPONSE=$(curl -s -X POST "$BASE_URL/api/teams" \
    -H 'Content-Type: application/json' \
    -d '{"tournament_id":1,"name":"Unauthorized Team","coach_id":1}')

if echo "$RESPONSE" | jq -e '.success == false' > /dev/null 2>&1; then
    print_pass "Unauthorized access properly blocked"
else
    print_fail "Unauthorized access not properly blocked"
fi

print_test "Testing invalid data validation..."
RESPONSE=$(curl -s -X POST "$BASE_URL/api/teams" \
    -H "Authorization: Bearer $TOKEN" \
    -H 'Content-Type: application/json' \
    -d '{"tournament_id":"invalid","name":"","coach_id":"invalid"}')

if echo "$RESPONSE" | jq -e '.success == false' > /dev/null 2>&1; then
    print_pass "Invalid data properly validated"
else
    print_fail "Invalid data validation failed"
fi

echo ""
print_pass "Team Service API testing completed!"
EOF

chmod +x test-team-api.sh

print_success "Created test-team-api.sh for manual testing"
print_status "Run './test-team-api.sh' to test the API endpoints"

echo ""
print_success "Setup complete! Team Service is running and ready for testing."
