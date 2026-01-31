#!/bin/bash

# Stop queue worker

echo "Stopping queue workers..."

# Kill by PID if file exists
if [ -f storage/queue-worker.pid ]; then
    PID=$(cat storage/queue-worker.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped queue worker (PID: $PID)"
    fi
    rm storage/queue-worker.pid
fi

# Also kill by process name
pkill -f "queue:work.*events" && echo "Stopped remaining queue workers" || echo "No queue workers found"

echo "Done."
