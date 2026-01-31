# Fix Queue Prefix Issue

## Problem
Match-service dispatches events to `laravel-database-queues:events-high` but results-service worker can't see them because of Redis prefix mismatch.

## Solution

### Option 1: Set Same REDIS_PREFIX in .env (Recommended)

Both services must use the same Redis prefix. Add to `results-service/.env`:

```env
REDIS_PREFIX=laravel-database-
```

Or set in `match-service/.env` to match results-service prefix.

### Option 2: Use Explicit Queue Connection

Update `results-service/config/queue.php` to use the same Redis connection:

```php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',  // Make sure this uses the same Redis connection
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 180),
    'block_for' => null,
    'after_commit' => false,
],
```

### Option 3: Check Current Prefix

Run this to see what prefix results-service is using:

```bash
cd results-service
php artisan tinker
>>> config('database.redis.options.prefix')
```

Then set `REDIS_PREFIX` in `.env` to match match-service.

## Quick Fix

1. **Check current jobs waiting:**
   ```bash
   redis-cli LLEN "laravel-database-queues:events-high"
   ```
   (Shows 32 jobs waiting!)

2. **Set REDIS_PREFIX in results-service/.env:**
   ```bash
   echo "REDIS_PREFIX=laravel-database-" >> results-service/.env
   ```

3. **Clear config cache:**
   ```bash
   cd results-service
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Restart queue worker:**
   ```bash
   # Kill old worker
   pkill -f "queue:work.*events-high"
   
   # Start new worker
   php artisan queue:work redis --queue=events-high --tries=3 --timeout=120 --sleep=3
   ```

5. **Verify it's working:**
   ```bash
   tail -f storage/logs/laravel.log | grep -E "(match.completed|Processing queue event)"
   ```

## Expected Result

After fixing, you should see:
- Events being consumed from `laravel-database-queues:events-high`
- Logs showing "Processing queue event" with `match.completed`
- Logs showing "MatchCompletedHandler" processing events
- Processed events in `processed_events` table
