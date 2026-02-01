# Real-Time Cache Invalidation for Live Matches

## Overview

The Match Service implements real-time cache invalidation for live matches. When match events occur (goals, cards, substitutions), the cache is immediately invalidated to ensure users always see the latest data.

## How It Works

### 1. Event Flow

```
Match Event Occurs
    ↓
Event Queued (High Priority)
    ↓
CacheInvalidationHandler Processes Event
    ↓
Cache Tags Invalidated
    ↓
Next Request Rebuilds Cache with Fresh Data
```

### 2. Cache Invalidation Rules

#### `match.event.recorded` / `match.event_added`
When a match event (goal, card, substitution) is recorded:
- `match:{match_id}`
- `public:match:{match_id}`
- `public:match:{match_id}:events`
- `public:matches:live`
- `public:tournament:{tournament_id}:matches`

#### `match.started`
When a match starts:
- `match:{match_id}`
- `public:match:{match_id}`
- `public:matches:live`
- `public:matches:today`

#### `match.completed`
When a match is completed:
- `match:{match_id}`
- `public:match:{match_id}`
- `public:match:{match_id}:*` (all match-related cache)
- `public:matches:live`
- `public:team:{home_team_id}:matches`
- `public:team:{away_team_id}:matches`

#### `match.score.updated`
When match score is updated:
- `match:{match_id}`
- `public:match:{match_id}`
- `public:match:{match_id}:events`
- `public:matches:live`
- `public:tournament:{tournament_id}:matches`

## Testing Cache Invalidation

### Method 1: Using the Test Script

```bash
cd match-service
./test-cache-invalidation.sh [match_id] [tournament_id]
```

### Method 2: Manual Testing

1. **Populate cache:**
   ```bash
   curl http://localhost:8004/api/public/matches/1
   curl http://localhost:8004/api/public/matches/1/events
   curl http://localhost:8004/api/public/matches/live
   ```

2. **Trigger a match event:**
   ```bash
   curl -X POST http://localhost:8004/api/matches/1/events \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{
       "team_id": 1,
       "player_id": 1,
       "event_type": "goal",
       "minute": 45,
       "description": "Test goal"
     }'
   ```

3. **Verify cache was invalidated:**
   ```bash
   # Check logs
   tail -f storage/logs/laravel.log | grep "Cache invalidated"
   
   # Check cache statistics
   php artisan cache:monitor --stats
   
   # Check cached keys
   php artisan cache:monitor --keys --pattern="public_api:match:*"
   ```

### Method 3: Using Artisan Commands

```bash
# View cache statistics
php artisan cache:monitor --stats

# View cached keys by pattern
php artisan cache:monitor --keys --pattern="public_api:match:*"
php artisan cache:monitor --keys --pattern="public_api:public:matches:live*"

# Reset cache statistics
php artisan cache:monitor --reset
```

## Monitoring Cache Performance

### Cache Statistics

The `PublicCacheService` tracks:
- **Hits**: Number of successful cache retrievals
- **Misses**: Number of cache misses (data fetched from source)
- **Hit Rate**: Percentage of requests served from cache
- **Invalidations**: Number of cache invalidations performed

### Viewing Statistics

```bash
php artisan cache:monitor --stats
```

Output example:
```
📊 Public API Cache Statistics

┌─────────────────────┬──────────┐
│ Metric              │ Value    │
├─────────────────────┼──────────┤
│ Total Requests      │ 1,234    │
│ Cache Hits          │ 987      │
│ Cache Misses        │ 247      │
│ Hit Rate            │ 80.06%  │
│ Invalidations       │ 45       │
│ Last Reset          │ 2026-01-31T10:00:00Z │
└─────────────────────┴──────────┘

Hit Rate: [████████████████████████████████████████░░░░░░░░░░] 80.06%

✅ Excellent cache performance!
```

### Performance Benchmarks

- **Excellent**: Hit rate ≥ 80%
- **Good**: Hit rate ≥ 60%
- **Moderate**: Hit rate ≥ 40%
- **Low**: Hit rate < 40%

### Monitoring in Production

1. **Set up log monitoring:**
   ```bash
   # Monitor cache invalidations
   tail -f storage/logs/laravel.log | grep "Cache invalidated"
   
   # Monitor cache misses
   tail -f storage/logs/laravel.log | grep "Public cache miss"
   ```

2. **Check Redis keys:**
   ```bash
   redis-cli KEYS "public_api:match:*"
   redis-cli KEYS "public_api:public:matches:live*"
   ```

3. **Monitor queue processing:**
   ```bash
   # Ensure queue worker is running
   php artisan queue:work redis --queue=events-high,events-default,events-low
   
   # Check queue status
   php artisan queue:monitor
   ```

## Cache Key Patterns

### Match-Specific Keys
- `public_api:match:{match_id}` - Match details
- `public_api:public:match:{match_id}:events` - Match events
- `public_api:public:match:{match_id}:*` - All match-related cache

### Tournament Keys
- `public_api:public:tournament:{tournament_id}:matches` - Tournament matches list

### Team Keys
- `public_api:public:team:{team_id}:matches*` - Team matches

### Global Keys
- `public_api:public:matches:live` - Live matches list
- `public_api:public:matches:today` - Today's matches
- `public_api:public:matches:upcoming` - Upcoming matches

## Troubleshooting

### Cache Not Invalidating

1. **Check queue worker is running:**
   ```bash
   php artisan queue:work redis --queue=events-high,events-default,events-low --verbose
   ```

2. **Check event is being dispatched:**
   ```bash
   tail -f storage/logs/laravel.log | grep "match.event.recorded"
   ```

3. **Check handler is registered:**
   ```bash
   # Verify in config/events.php
   grep -A 5 "handlers" config/events.php
   ```

4. **Check Redis connection:**
   ```bash
   php artisan tinker
   >>> Cache::getStore()->getRedis()->ping()
   ```

### Low Cache Hit Rate

1. **Check TTL settings:**
   - Live matches: 30 seconds
   - Match details: 2 minutes
   - Match events (live): 1 minute
   - Match events (completed): 1 hour

2. **Review invalidation frequency:**
   - Too many invalidations = lower hit rate
   - Check logs for invalidation patterns

3. **Check cache driver:**
   ```bash
   php artisan tinker
   >>> config('cache.default')
   ```

## Best Practices

1. **Use appropriate TTLs:**
   - Short TTL (30s-2min) for live data
   - Longer TTL (1 hour+) for static data

2. **Monitor hit rates:**
   - Aim for ≥ 80% hit rate
   - Adjust TTLs if hit rate is low

3. **Use pattern invalidation sparingly:**
   - Prefer tag-based invalidation
   - Use patterns only for complex scenarios

4. **Monitor queue processing:**
   - Ensure high-priority events are processed quickly
   - Use separate queues for different priorities

5. **Log cache operations:**
   - Enable debug logging for development
   - Monitor production logs for cache issues

## Related Files

- `app/Services/Events/Handlers/CacheInvalidationHandler.php` - Cache invalidation handler
- `app/Services/PublicCacheService.php` - Cache service with statistics
- `app/Console/Commands/CacheMonitorCommand.php` - Monitoring command
- `config/events.php` - Event handler configuration
