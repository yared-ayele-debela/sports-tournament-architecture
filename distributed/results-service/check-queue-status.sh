#!/bin/bash

echo "=== Queue Status Check ==="
echo ""

echo "1. Checking Redis queue sizes:"
redis-cli LLEN "queues:events-high" 2>/dev/null && echo "   events-high: $(redis-cli LLEN queues:events-high)"
redis-cli LLEN "queues:events-default" 2>/dev/null && echo "   events-default: $(redis-cli LLEN queues:events-default)"
redis-cli LLEN "queues:default" 2>/dev/null && echo "   default: $(redis-cli LLEN queues:default)"
redis-cli LLEN "queues:high" 2>/dev/null && echo "   high: $(redis-cli LLEN queues:high)"
echo ""

echo "2. Checking running queue workers:"
ps aux | grep "queue:work" | grep -v grep || echo "   No queue workers running"
echo ""

echo "3. Checking recent logs for event processing:"
tail -20 storage/logs/laravel.log | grep -E "(Processing queue event|ProcessEventJob dispatched|Event handled|match.completed|tournament.status.changed)" || echo "   No recent event processing logs"
echo ""

echo "4. Checking processed_events table:"
php artisan tinker --execute="echo DB::table('processed_events')->count() . ' processed events';" 2>/dev/null || echo "   Could not check processed_events table"
echo ""

echo "5. Checking failed jobs:"
php artisan queue:failed | head -5 || echo "   No failed jobs or error checking"
echo ""
