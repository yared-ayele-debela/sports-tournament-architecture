#!/bin/bash

echo "=== Testing Event Consumption ==="
echo ""

echo "1. Checking queue workers:"
ps aux | grep "queue:work.*events-high" | grep -v grep || echo "   ❌ No worker listening to events-high"
echo ""

echo "2. Checking queue sizes:"
echo "   events-high: $(redis-cli LLEN queues:events-high 2>/dev/null || echo '0')"
echo "   events-default: $(redis-cli LLEN queues:events-default 2>/dev/null || echo '0')"
echo "   default: $(redis-cli LLEN queues:default 2>/dev/null || echo '0')"
echo ""

echo "3. Checking recent logs for event processing:"
tail -30 storage/logs/laravel.log | grep -E "(Processing queue event|match.completed|MatchCompletedHandler|Processing queued event)" | tail -5 || echo "   No event processing logs found"
echo ""

echo "4. To test: Complete a match in match-service, then check logs:"
echo "   tail -f storage/logs/laravel.log | grep -E '(match.completed|Processing|Handler)'"
echo ""
