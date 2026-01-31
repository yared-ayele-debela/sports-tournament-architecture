#!/bin/bash

# Start queue worker in background for processing events
# This script runs the queue worker as a background process

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Kill any existing queue workers
echo "Stopping existing queue workers..."
pkill -f "queue:work.*events" || true
sleep 2

# Start queue worker in background
echo "Starting queue worker in background..."
echo "Queue: events-high,events-default,events-low"
echo "Logs: storage/logs/queue-worker.log"
echo ""

nohup php artisan queue:work redis \
    --queue=events-high,events-default,events-low \
    --tries=3 \
    --timeout=120 \
    --sleep=3 \
    --max-time=3600 \
    --verbose \
    >> storage/logs/queue-worker.log 2>&1 &

QUEUE_PID=$!
echo "Queue worker started with PID: $QUEUE_PID"
echo "To stop: pkill -f 'queue:work.*events'"
echo "To view logs: tail -f storage/logs/queue-worker.log"
echo "To check status: ps aux | grep 'queue:work'"

# Save PID to file for easy management
echo $QUEUE_PID > storage/queue-worker.pid
echo "PID saved to: storage/queue-worker.pid"
