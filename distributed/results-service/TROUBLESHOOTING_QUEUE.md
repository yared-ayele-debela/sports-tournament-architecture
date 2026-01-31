# Troubleshooting Queue Event Consumption

## Issue: match.completed events not being consumed

### Step 1: Verify Queue Workers Are Running

Check if queue workers are running:

```bash
# Check supervisor status
supervisorctl status

# Or check processes manually
ps aux | grep "queue:work"

# Or check in Docker
docker-compose exec results-service ps aux | grep "queue:work"
```

### Step 2: Start Queue Workers

If workers aren't running, start them:

**Option A: Using Supervisor (Recommended)**
```bash
# Copy supervisor config
sudo cp supervisord-queue.conf /etc/supervisor/conf.d/results-service-queue.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start results-service-queue-high:*
sudo supervisorctl start results-service-queue-default:*
sudo supervisorctl start results-service-queue-low:*
```

**Option B: Manual Start (For Testing)**
```bash
# Start high priority queue worker (for match.completed)
php artisan queue:work redis --queue=events-high --tries=3 --timeout=120 --sleep=3

# Or in Docker
docker-compose exec results-service php artisan queue:work redis --queue=events-high --tries=3 --timeout=120 --sleep=3
```

### Step 3: Verify Queue Configuration

Check that the queue connection is set correctly:

```bash
php artisan tinker
>>> config('queue.default')
# Should return: "redis"
```

Check `.env` file:
```env
QUEUE_CONNECTION=redis
```

### Step 4: Check Queue Status

```bash
# Check queue sizes
php artisan queue:monitor --stats

# Check Redis queues directly
redis-cli LLEN "queues:events-high"
redis-cli LLEN "queues:events-default"
```

### Step 5: Verify Event Handler Registration

```bash
php artisan tinker
>>> config('events.handlers')
# Should show:
# [
#   'match.completed' => 'App\Handlers\MatchCompletedHandler',
#   'tournament.status.changed' => 'App\Handlers\TournamentStatusChangedHandler',
# ]
```

### Step 6: Test Event Processing

Manually test by dispatching a test event:

```bash
php artisan tinker
>>> $event = [
    'event_id' => \Illuminate\Support\Str::uuid(),
    'event_type' => 'match.completed',
    'service' => 'match-service',
    'payload' => [
        'match_id' => 1,
        'tournament_id' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'home_score' => 2,
        'away_score' => 1,
        'result' => 'home_win',
        'completed_at' => now()->toIso8601String(),
    ],
    'timestamp' => now()->toIso8601String(),
    'version' => '1.0',
    'retry_count' => 0,
    'max_retries' => 3,
];
>>> dispatch(new \App\Jobs\ProcessEventJob($event))->onQueue('events-high');
```

### Step 7: Check Logs

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log | grep -i "match.completed\|Processing\|Event processed"

# Check supervisor logs
tail -f /var/log/supervisor/results-service-queue-high.log
```

### Step 8: Verify Database Migration

Make sure the `processed_events` table exists:

```bash
php artisan migrate:status
php artisan migrate
```

### Common Issues

1. **Queue workers not running**: Most common issue. Start workers using supervisor or manually.

2. **Wrong queue name**: Match-service dispatches to `events-high`, make sure workers are listening to `events-high`.

3. **Redis connection issues**: Verify Redis is accessible and connection is configured correctly.

4. **Handler not registered**: Check `config/events.php` has the correct handler mapping.

5. **Event structure mismatch**: Verify the event payload matches what the handler expects.

### Debugging Commands

```bash
# Watch queue in real-time
watch -n 1 'php artisan queue:monitor --stats'

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```
