#!/bin/bash

# Test script for real-time cache invalidation in Match Service
# This script tests cache invalidation when match events occur

BASE_URL="${MATCH_SERVICE_URL:-http://localhost:8004}"
MATCH_ID="${1:-1}"
TOURNAMENT_ID="${2:-1}"

echo "🧪 Testing Real-Time Cache Invalidation for Match Service"
echo "=========================================================="
echo "Base URL: $BASE_URL"
echo "Match ID: $MATCH_ID"
echo "Tournament ID: $TOURNAMENT_ID"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to make API call and check cache headers
test_endpoint() {
    local endpoint=$1
    local description=$2
    
    echo -e "${YELLOW}Testing: $description${NC}"
    echo "GET $endpoint"
    
    response=$(curl -s -w "\n%{http_code}" "$BASE_URL$endpoint")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -eq 200 ]; then
        echo -e "${GREEN}✓ Status: $http_code${NC}"
        # Check if response has cached data
        if echo "$body" | grep -q '"cached"'; then
            cached=$(echo "$body" | grep -o '"cached":[^,]*' | cut -d':' -f2 | tr -d ' ')
            echo "  Cached: $cached"
        fi
    else
        echo -e "${RED}✗ Status: $http_code${NC}"
        echo "  Response: $body"
    fi
    echo ""
}

# Step 1: Initial cache population
echo "📦 Step 1: Populating cache with initial requests"
echo "---------------------------------------------------"
test_endpoint "/api/public/matches/live" "Live matches"
test_endpoint "/api/public/matches/$MATCH_ID" "Match details"
test_endpoint "/api/public/matches/$MATCH_ID/events" "Match events"
test_endpoint "/api/public/tournaments/$TOURNAMENT_ID/matches" "Tournament matches"
test_endpoint "/api/public/matches/today" "Today's matches"

echo "⏳ Waiting 2 seconds for cache to settle..."
sleep 2

# Step 2: Verify cache is working (second request should be faster)
echo ""
echo "🔄 Step 2: Verifying cache is working"
echo "--------------------------------------"
echo "Making second request to same endpoints (should be cached)..."
test_endpoint "/api/public/matches/$MATCH_ID" "Match details (cached)"

# Step 3: Simulate match event (this would normally be done via API)
echo ""
echo "⚡ Step 3: Simulating match event"
echo "----------------------------------"
echo "NOTE: To test real invalidation, you need to:"
echo "  1. Create a match event via POST /api/matches/{id}/events"
echo "  2. Or update match status via PATCH /api/matches/{id}/status"
echo ""
echo "Example commands:"
echo "  curl -X POST \"$BASE_URL/api/matches/$MATCH_ID/events\" \\"
echo "    -H \"Content-Type: application/json\" \\"
echo "    -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "    -d '{\"team_id\": 1, \"player_id\": 1, \"event_type\": \"goal\", \"minute\": 45, \"description\": \"Test goal\"}'"
echo ""

# Step 4: Check cache after event
echo ""
echo "🔍 Step 4: Checking cache after event"
echo "--------------------------------------"
echo "After triggering a match event, run:"
echo "  php artisan cache:monitor --stats"
echo "  php artisan cache:monitor --keys --pattern='public_api:match:*'"
echo ""

# Step 5: Monitor cache statistics
echo ""
echo "📊 Step 5: Cache Statistics"
echo "---------------------------"
echo "Run the following command to see cache statistics:"
echo "  php artisan cache:monitor --stats"
echo ""

# Step 6: Test pattern-based invalidation
echo ""
echo "🎯 Step 6: Testing Pattern-Based Invalidation"
echo "----------------------------------------------"
echo "To test pattern invalidation, check Redis keys:"
echo "  redis-cli KEYS 'public_api:match:*'"
echo "  redis-cli KEYS 'public_api:public:match:*'"
echo ""

echo "✅ Test script completed!"
echo ""
echo "Next steps:"
echo "  1. Trigger a match event via API"
echo "  2. Check logs: tail -f storage/logs/laravel.log | grep 'Cache invalidated'"
echo "  3. Monitor cache: php artisan cache:monitor --stats"
echo "  4. Verify cache keys: php artisan cache:monitor --keys --pattern='public_api:match:*'"
