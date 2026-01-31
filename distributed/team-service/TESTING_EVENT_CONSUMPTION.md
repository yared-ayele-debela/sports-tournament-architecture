# Testing Event Consumption in Team Service

## Prerequisites

1. **Queue workers must be running** (via Supervisor or manually)
2. **Redis must be running and accessible**
3. **Laravel logs should be monitored**

---

## Step 1: Check Queue Workers Are Running

### Option A: Check Supervisor Status
```bash
sudo supervisorctl status
```

You should see:
- `team-service-queue-high`
- `team-service-queue-default`
- `team-service-queue-low`

### Option B: Check Manually Running Workers
```bash
ps aux | grep "queue:work"
```

### Option C: Start Queue Worker Manually (for testing)
```bash
cd team-service
php artisan queue:work redis --queue=default,events-default --tries=3 --timeout=120
```

---

## Step 2: Test Event Consumption

### Method 1: Use the Test Command (Recommended)

```bash
cd team-service

# Test tournament.created event
php artisan test:event-consumption tournament.created

# Test tournament.status.changed event
php artisan test:event-consumption tournament.status.changed

# Test with custom payload
php artisan test:event-consumption tournament.created --payload='{"tournament_id":123,"name":"Test Tournament","status":"upcoming"}'
```

### Method 2: Manually Dispatch via Tinker

```bash
cd team-service
php artisan tinker
```

Then in tinker:
```php
use App\Jobs\ProcessEventJob;
use Illuminate\Support\Str;
use Carbon\Carbon;

$event = [
    'event_id' => (string) Str::uuid(),
    'event_type' => 'tournament.created',
    'service' => 'tournament-service',
    'payload' => [
        'tournament_id' => 1,
        'name' => 'Test Tournament',
        'status' => 'upcoming',
        'start_date' => Carbon::now()->addDays(30)->toIso8601String(),
        'end_date' => Carbon::now()->addDays(60)->toIso8601String(),
        'sport_id' => 1,
    ],
    'timestamp' => Carbon::now()->utc()->toIso8601String(),
    'version' => '1.0',
    'retry_count' => 0,
    'max_retries' => 3,
];

dispatch(new ProcessEventJob($event));
```

---

## Step 3: Monitor Logs

### Watch Laravel Logs in Real-Time
```bash
cd team-service
tail -f storage/logs/laravel.log
```

### What to Look For:

**✅ Success Indicators:**
- `Processing queued event` - Event received
- `Event handled by handler` - Handler found and called
- `Processing event` - Handler started processing
- `Event processed successfully` - Handler completed

**❌ Error Indicators:**
- `No handler found for event type` - Handler not registered
- `Handler failed to process event` - Handler error
- `Event validation error` - Invalid event structure

---

## Step 4: Verify Results

### For `tournament.created` Event:

1. **Check Cache:**
```bash
cd team-service
php artisan tinker
```

```php
use Illuminate\Support\Facades\Cache;
Cache::get('tournament:1'); // Should return tournament data
```

2. **Check Logs:**
Look for: `Tournament created event processed`

### For `tournament.status.changed` Event:

1. **Check Cache:**
```php
use Illuminate\Support\Facades\Cache;
$data = Cache::get('tournament:1');
$data['status']; // Should be 'completed' if you tested with status=completed
```

2. **Check Database (if teams table has locked column):**
```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\DB;
DB::table('teams')->where('tournament_id', 1)->get(['id', 'locked', 'locked_at']);
```

3. **Check Logs:**
Look for: `Tournament status changed event processed` and `Teams locked for completed tournament`

---

## Step 5: Check Redis Queue Status

### View Queue Length
```bash
redis-cli
```

```redis
LLEN queues:default
LLEN queues:events-default
LLEN queues:events-high
```

### View Pending Jobs
```redis
LRANGE queues:default 0 -1
```

### Clear Queue (if needed for testing)
```redis
DEL queues:default
DEL queues:events-default
```

---

## Step 6: Check Failed Jobs

### View Failed Jobs Table
```bash
cd team-service
php artisan tinker
```

```php
use Illuminate\Support\Facades\DB;
DB::table('failed_jobs')->latest()->take(5)->get();
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

---

## Quick Test Checklist

- [ ] Queue workers are running
- [ ] Redis is accessible
- [ ] Test command executed successfully
- [ ] Logs show "Processing queued event"
- [ ] Logs show "Event handled by handler"
- [ ] Logs show "Event processed successfully"
- [ ] Cache contains tournament data (for tournament.created)
- [ ] Teams are locked (for tournament.status.changed with status=completed)
- [ ] No errors in logs

---

## Troubleshooting

### Issue: "No handler found for event type"
**Solution:** Check `config/events.php` - handlers must be registered

### Issue: Queue worker not processing jobs
**Solution:** 
- Check if worker is running: `ps aux | grep queue:work`
- Check Redis connection: `redis-cli ping`
- Restart worker: `php artisan queue:restart`

### Issue: Jobs stuck in queue
**Solution:**
- Check if workers are running
- Check Redis connection
- Clear and retry: `php artisan queue:flush && php artisan queue:retry all`

### Issue: Handler not being called
**Solution:**
- Verify handler is in `config/events.php`
- Check handler implements `EventHandlerInterface`
- Check handler's `getHandledEventTypes()` returns correct event type
- Check logs for handler loading errors

---

## Example Test Flow

```bash
# 1. Start queue worker (in one terminal)
cd team-service
php artisan queue:work redis --queue=default,events-default --verbose

# 2. In another terminal, dispatch test event
cd team-service
php artisan test:event-consumption tournament.created

# 3. Watch logs (in third terminal)
cd team-service
tail -f storage/logs/laravel.log

# 4. Verify cache
php artisan tinker
>>> Cache::get('tournament:1')
```

---

## Expected Log Output

When event consumption works correctly, you should see:

```
[2026-01-31 16:20:00] local.INFO: Processing queued event {"event_id":"...","event_type":"tournament.created",...}
[2026-01-31 16:20:00] local.DEBUG: Event handled by handler {"handler_class":"App\\Handlers\\TournamentCreatedHandler",...}
[2026-01-31 16:20:00] local.INFO: Processing event {"event_id":"...","event_type":"tournament.created",...}
[2026-01-31 16:20:00] local.INFO: Tournament created event processed {"tournament_id":1,...}
[2026-01-31 16:20:00] local.INFO: Event processed successfully {"event_id":"...",...}
```
