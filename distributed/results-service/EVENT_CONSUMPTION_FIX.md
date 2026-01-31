# Event Consumption Fix for Results Service

## Problem
Events are being dispatched from match-service but results-service is not consuming them.

## Root Causes

1. **Queue Worker Listening to Wrong Queues**
   - Current worker: `php artisan queue:work redis --queue=high,default`
   - Should be: `php artisan queue:work redis --queue=events-high`
   - Match-service dispatches to `events-high` queue
   - Results-service workers must listen to `events-high` to receive events

2. **No Logs Showing Event Processing**
   - No logs for "Processing queued event" with `match.completed` or `tournament.status.changed`
   - Only seeing `standings.updated` events (which are published by results-service itself)

## Solution

### Step 1: Stop Current Workers
```bash
# Kill any running workers listening to wrong queues
pkill -f "queue:work redis --queue=high,default"
```

### Step 2: Start Correct Workers
```bash
cd results-service

# Start worker for high priority events (match.completed, tournament.status.changed)
php artisan queue:work redis --queue=events-high --tries=3 --timeout=120 --sleep=3 &

# Start worker for default priority events
php artisan queue:work redis --queue=events-default,default --tries=3 --timeout=120 --sleep=3 &

# Start worker for low priority events
php artisan queue:work redis --queue=events-low --tries=3 --timeout=120 --sleep=3 &
```

### Step 3: Use Supervisor (Recommended)
```bash
# Copy supervisor config
sudo cp results-service/supervisord-queue.conf /etc/supervisor/conf.d/results-service-queue.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start results-service-queue-high:*
sudo supervisorctl start results-service-queue-default:*
sudo supervisorctl start results-service-queue-low:*
```

### Step 4: Verify Workers Are Running
```bash
ps aux | grep "queue:work" | grep -v grep
```

Should see:
- `queue:work redis --queue=events-high`
- `queue:work redis --queue=events-default,default`
- `queue:work redis --queue=events-low`

### Step 5: Test Event Consumption

1. **Complete a match in match-service** (via API)
2. **Check results-service logs**:
   ```bash
   tail -f results-service/storage/logs/laravel.log | grep -E "(match.completed|Processing queue event|MatchCompletedHandler)"
   ```

3. **Check processed events**:
   ```bash
   php artisan queue:monitor --processed
   ```

4. **Check queue sizes**:
   ```bash
   redis-cli LLEN queues:events-high
   ```

## Expected Log Flow

When `match.completed` event is consumed, you should see:

1. `Processing queue event` - QueueEventJob receives the event
2. `ProcessEventJob dispatched` - ProcessEventJob is dispatched
3. `Processing queued event` - ProcessEventJob starts processing
4. `Processing match.completed event` - MatchCompletedHandler starts
5. `Match result stored` - MatchResult saved
6. `Standings updated from match` - StandingsCalculator runs
7. `Match result processed successfully` - Handler completes
8. `standings.updated` event dispatched

## Troubleshooting

### If events still not consumed:

1. **Check queue names match**:
   - Match-service dispatches to: `events-high`
   - Results-service listens to: `events-high` ✓

2. **Check Redis connection**:
   ```bash
   redis-cli PING
   ```

3. **Check event structure**:
   - Event must have: `event_id`, `event_type`, `service`, `payload`, `timestamp`, `version`
   - Event type must be: `match.completed` or `tournament.status.changed`

4. **Check handlers are registered**:
   ```bash
   php artisan tinker
   >>> config('events.handlers')
   ```
   Should show:
   ```php
   [
     'match.completed' => 'App\Handlers\MatchCompletedHandler',
     'tournament.status.changed' => 'App\Handlers\TournamentStatusChangedHandler',
   ]
   ```

5. **Check for errors in logs**:
   ```bash
   tail -100 results-service/storage/logs/laravel.log | grep -i error
   ```

## Quick Test Command

```bash
# In results-service directory
php artisan queue:work redis --queue=events-high --once --verbose
```

This will process one job and show detailed output.
