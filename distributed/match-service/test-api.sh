#!/bin/bash

echo "=== Match Service API Testing ==="
echo "Server: http://localhost:8004"
echo

echo "1. Health Check (Public):"
curl -s http://localhost:8004/api/health | jq .
echo

echo "2. Protected Routes (without token):"
echo "   - GET /api/tournaments/1/matches:"
curl -s http://localhost:8004/api/tournaments/1/matches | jq '.message'
echo

echo "3. Protected Routes (with invalid token):"
echo "   - GET /api/tournaments/1/matches:"
curl -s -H "Authorization: Bearer invalid-token" http://localhost:8004/api/tournaments/1/matches | jq '.message'
echo

echo "4. Database Verification:"
echo "   - Total matches in database:"
php artisan tinker --execute="echo App\Models\MatchGame::count();"
echo

echo "   - Total events in database:"
php artisan tinker --execute="echo App\Models\MatchEvent::count();"
echo

echo "   - Total reports in database:"
php artisan tinker --execute="echo App\Models\MatchReport::count();"
echo

echo "=== Test Complete ==="
