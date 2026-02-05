#!/bin/bash

# Restart queue worker script
# This script stops any running queue workers and starts a new one

echo "Stopping existing queue workers..."
pkill -f "queue:work redis"

sleep 2

echo "Starting queue worker..."
cd "$(dirname "$0")"
nohup php artisan queue:work redis --queue=events-default,events-high,events-low --tries=3 --timeout=120 --sleep=3 > storage/logs/queue-worker.log 2>&1 &

echo "Queue worker started in background. PID: $!"
echo "Logs: tail -f storage/logs/queue-worker.log"
