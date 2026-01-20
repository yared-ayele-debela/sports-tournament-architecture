#!/bin/bash

# Simple EventPublisher Test
# Multiple ways to test if EventPublisher is working

echo "🔍 EventPublisher Testing Guide"
echo "=============================="

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "\n${BLUE}📋 Method 1: Manual Redis Test${NC}"
echo "----------------------------------------"
echo "1. Open Terminal 1 and run:"
echo "   redis-cli subscribe sports-tournament-events"
echo ""
echo "2. Open Terminal 2 and run:"
echo "   curl -X POST http://localhost:8002/api/tournaments \\"
echo "     -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "     -H \"Content-Type: application/json\" \\"
echo "     -d '{\"sport_id\":1,\"name\":\"Test Tournament\",\"start_date\":\"2026-06-01\",\"end_date\":\"2026-07-01\"}'"
echo ""
echo "3. You should see the event in Terminal 1"

echo -e "\n${BLUE}📋 Method 2: Check Laravel Logs${NC}"
echo "----------------------------------------"
echo "Run this command to see event publishing logs:"
echo "tail -f storage/logs/laravel.log | grep 'Event published'"
echo ""
echo "Then create a tournament and watch for logs like:"
echo "[2026-01-20 12:00:00] local.INFO: Event published"

echo -e "\n${BLUE}📋 Method 3: Test EventPublisher Directly${NC}"
echo "----------------------------------------"
echo "Create a simple PHP test script:"
echo ""
cat << 'EOF'
<?php
// test_event_publisher.php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$eventPublisher = app(App\Services\EventPublisher::class);

// Test basic publishing
$result = $eventPublisher->publish('test.event', [
    'message' => 'Hello from EventPublisher!',
    'timestamp' => now()
]);

echo $result ? "✅ Event published successfully" : "❌ Event publishing failed";
echo "\n";
EOF

echo -e "\n${BLUE}📋 Method 4: Check Redis Directly${NC}"
echo "----------------------------------------"
echo "1. Check if Redis is receiving events:"
echo "   redis-cli monitor"
echo ""
echo "2. Check channel subscribers:"
echo "   redis-cli pubsub channels"
echo ""
echo "3. Check specific channel:"
echo "   redis-cli pubsub numsub sports-tournament-events"

echo -e "\n${BLUE}📋 Method 5: Use Artisan Tinker${NC}"
echo "----------------------------------------"
echo "Run: php artisan tinker"
echo "Then execute:"
echo ""
echo "\$eventPublisher = app(App\Services\EventPublisher::class);"
echo "\$eventPublisher->publish('test.event', ['message' => 'Testing']);"

echo -e "\n${BLUE}📋 Method 6: Check Service Status${NC}"
echo "----------------------------------------"
echo "Test if EventPublisher can connect to Redis:"
echo ""
cat << 'EOF'
<?php
// test_connection.php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$eventPublisher = app(App\Services\EventPublisher::class);

if ($eventPublisher->testConnection()) {
    echo "✅ Redis connection successful\n";
    echo "Channel: " . $eventPublisher->getChannel() . "\n";
} else {
    echo "❌ Redis connection failed\n";
}
EOF

echo -e "\n${YELLOW}⚠️  Quick Test Commands:${NC}"
echo "========================"
echo "# Check Redis status"
echo "redis-cli ping"
echo ""
echo "# Check current tournaments (to see if service works)"
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" http://localhost:8002/api/tournaments"
echo ""
echo "# Check service logs"
echo "tail -20 storage/logs/laravel.log"
echo ""
echo "# Check Redis channels"
echo "redis-cli pubsub channels"

echo -e "\n${GREEN}🎯 Expected Event Format:${NC}"
echo "========================"
echo '{
    "type": "tournament.created",
    "payload": {
        "tournament": {...},
        "action": "created",
        "timestamp": "2026-01-20T12:00:00.000000Z"
    },
    "timestamp": "2026-01-20T12:00:00.000000Z",
    "service": "tournament-service",
    "version": "1.0",
    "id": "event_1234567890abcdef"
}'

echo -e "\n${BLUE}📝 Troubleshooting Tips:${NC}"
echo "========================"
echo "1. If Redis connection fails:"
echo "   - Check if Redis is running: redis-server"
echo "   - Check Redis config in .env file"
echo ""
echo "2. If no events are published:"
echo "   - Check if user has permissions to create tournaments"
echo "   - Check Laravel logs for errors"
echo "   - Verify EventPublisher is injected in controller"
echo ""
echo "3. If events not received:"
echo "   - Check channel name matches in config/events.php"
echo "   - Verify Redis subscriber is listening to correct channel"
echo "   - Check if events are enabled in config"
