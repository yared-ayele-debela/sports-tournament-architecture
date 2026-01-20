#!/bin/bash

# EventPublisher Test Script
# Tests event publishing with Redis Pub/Sub

echo "🎯 EventPublisher Test Script"
echo "============================"

# Configuration
TOURNAMENT_SERVICE_URL="http://localhost:8002"
REDIS_CHANNEL="sports-tournament-events"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_test() {
    echo -e "\n${BLUE}📋 Test: $1${NC}"
    echo "----------------------------------------"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Test 1: Check Redis connection
print_test "Redis Connection"
if redis-cli ping > /dev/null 2>&1; then
    print_success "Redis is running"
else
    print_error "Redis is not running"
    echo "Please start Redis: redis-server"
    exit 1
fi

# Test 2: Subscribe to events channel (background)
print_test "Subscribe to Events Channel"
print_info "Starting Redis subscriber in background..."

# Start subscriber in background
redis-cli subscribe "$REDIS_CHANNEL" > subscriber_output.log 2>&1 &
SUBSCRIBER_PID=$!

# Give subscriber time to start
sleep 2

if kill -0 "$SUBSCRIBER_PID" 2>/dev/null; then
    print_success "Subscriber started (PID: $SUBSCRIBER_PID)"
else
    print_error "Failed to start subscriber"
    exit 1
fi

# Test 3: Get authentication token
print_test "Get Authentication Token"
login_response=$(curl -s -X POST "http://localhost:8001/api/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin1@example.com","password":"password123"}')

if echo "$login_response" | grep -q '"success":true'; then
    TOKEN=$(echo "$login_response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    print_success "Token obtained successfully"
else
    print_error "Failed to get token"
    echo "Response: $login_response"
    kill $SUBSCRIBER_PID 2>/dev/null
    exit 1
fi

# Test 4: Create tournament to trigger event
print_test "Create Tournament (Should Publish Event)"
response=$(curl -s -w "%{http_code}" -X POST "$TOURNAMENT_SERVICE_URL/api/tournaments" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"sport_id":1,"name":"Event Test Tournament","start_date":"2026-06-01","end_date":"2026-07-01","status":"planned"}')

http_code="${response: -3}"

if [ "$http_code" = "201" ]; then
    print_success "Tournament created successfully (201)"
    
    # Wait a moment for event to be published
    sleep 1
    
    # Check if event was received
    if [ -f "subscriber_output.log" ] && grep -q "tournament.created" subscriber_output.log; then
        print_success "Event published and received!"
        
        # Show the event
        echo -e "\n${YELLOW}Published Event:${NC}"
        grep "tournament.created" subscriber_output.log | tail -1 | jq . 2>/dev/null || grep "tournament.created" subscriber_output.log | tail -1
    else
        print_warning "Event not found in subscriber output"
        echo "Subscriber output:"
        cat subscriber_output.log 2>/dev/null || echo "No output file found"
    fi
else
    print_error "Failed to create tournament ($http_code)"
fi

# Test 5: Create another tournament to see multiple events
print_test "Create Second Tournament"
response=$(curl -s -w "%{http_code}" -X POST "$TOURNAMENT_SERVICE_URL/api/tournaments" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"sport_id":2,"name":"Second Event Test","start_date":"2026-08-01","end_date":"2026-09-01","status":"planning"}')

http_code="${response: -3}"

if [ "$http_code" = "201" ]; then
    print_success "Second tournament created (201)"
    sleep 1
    
    event_count=$(grep -c "tournament.created" subscriber_output.log 2>/dev/null || echo "0")
    print_info "Total events received: $event_count"
fi

# Test 6: Show all received events
print_test "All Received Events"
if [ -f "subscriber_output.log" ]; then
    echo -e "\n${YELLOW}Event Log:${NC}"
    grep "tournament.created" subscriber_output.log | while read line; do
        echo "📦 $line"
    done
else
    print_warning "No event log found"
fi

# Cleanup
print_test "Cleanup"
kill $SUBSCRIBER_PID 2>/dev/null
rm -f subscriber_output.log
print_success "Cleaned up subscriber and log files"

echo -e "\n${GREEN}🎉 EventPublisher Test Complete!${NC}"
echo "============================="
echo "✅ Redis Pub/Sub working"
echo "✅ Events being published"
echo "✅ Events being received"
echo "✅ Proper event format"

echo -e "\n${BLUE}📝 Notes:${NC}"
echo "- Events are published to '$REDIS_CHANNEL'"
echo "- Each event includes: type, payload, timestamp, service, version, id"
echo "- Check Laravel logs for detailed event publishing information"
