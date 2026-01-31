# Troubleshooting: Team-Service Not Consuming Tournament Status Changed Events

## Problem
When tournament status changes in tournament-service, team-service is not consuming the events.

## Root Cause
The event flow has two jobs:
1. `QueueEventJob` - Dispatched to `events-high` queue
2. `ProcessEventJob` - Dispatched by QueueEventJob but wasn't specifying the queue

## Solution Applied
Updated `QueueEventJob` to dispatch `ProcessEventJob` to the same queue.

## Verification Steps

### 1. Check Queue Workers Are Running

```bash
# Check supervisor status
sudo supervisorctl status

# Or check manually
ps aux | grep "queue:work"
```

**Expected:** You should see workers for:
- `team-service-queue-high` (listening to `events-high`)
- `team-service-queue-default` (listening to `events-default,default`)

### 2. Check If Events Are Being Dispatched

**In tournament-service logs:**
```bash
tail -f tournament-service/storage/logs/laravel.log | grep "tournament.status.changed"
```

**Expected:** You should see:
```
Queue job dispatched successfully {"event_type":"tournament.status.changed","queue":"events","priority":"high",...}
```

### 3. Check If Events Are In Redis Queue

```bash
redis-cli
```

```redis
# Check queue length
LLEN queues:events-high

# View pending jobs (first 10)
LRANGE queues:events-high 0 9
```

**Expected:** If events are being dispatched, you should see jobs in the queue.

### 4. Check Team-Service Logs

```bash
tail -f team-service/storage/logs/laravel.log
```

**Look for:**
- `Processing queue event` - QueueEventJob received
- `Processing queued event` - ProcessEventJob received
- `Event handled by handler` - Handler found
- `Tournament status changed event processed` - Handler completed

### 5. Test Manually

```bash
cd team-service
php artisan test:event-consumption tournament.status.changed --queue=events-high
```

**Expected:** You should see the handler process the event and update the cache.

## Common Issues

### Issue 1: Queue Workers Not Running
**Symptoms:** No logs, events stuck in queue
**Solution:**
```bash
# Start workers manually
cd team-service
php artisan queue:work redis --queue=events-high --verbose

# Or restart supervisor
sudo supervisorctl restart team-service-queue-high
```

### Issue 2: Wrong Queue Name
**Symptoms:** Events dispatched but not processed
**Check:**
- Tournament-service dispatches to: `events-high`
- Team-service listens to: `events-high`
- Both must match!

### Issue 3: Handler Not Registered
**Symptoms:** "No handler found for event type"
**Check:**
```bash
cd team-service
php artisan tinker
```

```php
config('events.handlers');
// Should show: ['tournament.status.changed' => 'App\Handlers\TournamentStatusChangedHandler']
```

### Issue 4: Redis Connection Issues
**Symptoms:** "Redis connection failed"
**Check:**
```bash
redis-cli ping
# Should return: PONG
```

### Issue 5: ProcessEventJob Not On Same Queue
**Fixed:** QueueEventJob now dispatches ProcessEventJob to the same queue.

## Quick Diagnostic Commands

```bash
# 1. Check workers
ps aux | grep queue:work

# 2. Check queue length
redis-cli LLEN queues:events-high

# 3. Check recent logs
tail -20 team-service/storage/logs/laravel.log | grep -i "tournament\|event\|handler"

# 4. Test event consumption
cd team-service
php artisan test:event-consumption tournament.status.changed

# 5. Check cache (after event processed)
php artisan tinker
>>> Cache::get('tournament:1')
```

## Expected Flow

1. **Tournament-service** changes status → dispatches `QueueEventJob` to `events-high`
2. **Team-service worker** (listening to `events-high`) picks up `QueueEventJob`
3. `QueueEventJob` dispatches `ProcessEventJob` to same queue (`events-high`)
4. **Team-service worker** picks up `ProcessEventJob`
5. `ProcessEventJob` loads handler from config
6. `TournamentStatusChangedHandler` processes the event
7. Cache updated, teams locked (if status=completed)

## Monitoring

Watch logs in real-time:
```bash
# Terminal 1: Tournament-service logs
tail -f tournament-service/storage/logs/laravel.log

# Terminal 2: Team-service logs  
tail -f team-service/storage/logs/laravel.log

# Terminal 3: Redis queue monitoring
watch -n 1 'redis-cli LLEN queues:events-high'
```
