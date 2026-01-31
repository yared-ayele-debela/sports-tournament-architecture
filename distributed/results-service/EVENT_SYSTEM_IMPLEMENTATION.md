# Results Service Event System Implementation

## Overview

Complete event system implementation for Results Service that processes match results and updates standings.

## Architecture

### Event Flow

```
Match Service
    ↓ (publishes)
sports.match.completed
    ↓ (subscribed by)
Results Service EventSubscriber
    ↓ (routes to)
MatchCompletedEventHandler
    ↓ (processes)
- Creates/Updates MatchResult
- Updates Standings via StandingsCalculator
    ↓ (publishes)
sports.standings.updated
sports.statistics.updated
```

## Components

### 1. EventPublisher (`app/Services/Events/EventPublisher.php`)
- Publishes events to Redis Pub/Sub channels
- Retry logic with exponential backoff
- Event structure standardization
- Health check support

**Events Published:**
- `sports.standings.updated` - When standings are recalculated
- `sports.statistics.updated` - When tournament statistics are updated
- `sports.standings.recalculated` - When full recalculation completes

### 2. EventSubscriber (`app/Services/Events/EventSubscriber.php`)
- Subscribes to Redis Pub/Sub channels
- Automatic reconnection on failure
- Graceful shutdown handling
- Event validation and parsing

**Channels Subscribed:**
- `sports.match.completed` - **CRITICAL** - Main event that triggers standings calculation
- `sports.tournament.status.changed` - Triggers full recalculation if tournament completed

### 3. MatchCompletedEventHandler (`app/Services/Events/Handlers/MatchCompletedEventHandler.php`)
**CRITICAL HANDLER** - Processes all match completion events

**Features:**
- ✅ **Idempotency**: Prevents duplicate processing using event_id
- ✅ **Retry Logic**: 3 attempts with exponential backoff
- ✅ **Dead Letter Queue**: Failed events stored for manual review
- ✅ **Full Match Validation**: Optionally fetches match details from match-service
- ✅ **Standings Calculation**: Updates tournament standings
- ✅ **Event Publishing**: Publishes standings.updated and statistics.updated events

**Process Flow:**
1. Receive `sports.match.completed` event
2. Check idempotency (prevent duplicate processing)
3. Validate payload structure
4. Optionally fetch full match details from match-service
5. Create/update MatchResult in database
6. Update standings via StandingsCalculator
7. Mark event as processed
8. Publish `sports.standings.updated` event
9. Publish `sports.statistics.updated` event

**Error Handling:**
- Retries up to 3 times with exponential backoff
- Failed events sent to dead letter queue
- Alerts configured via `events.error_handling.alert_on_failures`

### 4. TournamentEventHandler (`app/Services/Events/Handlers/TournamentEventHandler.php`)
- Handles tournament status changes
- Triggers full standings recalculation when tournament completed
- Retry logic for recalculation failures

**Handles:**
- `sports.tournament.status.changed` - When tournament status changes
- `sports.tournament.completed` - When tournament is marked as completed

### 5. BaseEventHandler (`app/Contracts/BaseEventHandler.php`)
- Abstract base class for all event handlers
- Common validation and error handling
- Logging utilities

### 6. EventsListenCommand (`app/Console/Commands/EventsListenCommand.php`)
- Console command to start event listener daemon
- Loads handlers from config
- Graceful shutdown support

## Configuration

### `config/events.php`

**Channels:**
```php
'channels' => [
    'sports.match.completed',           // MAIN EVENT
    'sports.tournament.status.changed',  // Full recalculation trigger
],
```

**Handlers:**
```php
'handlers' => [
    'sports.match.completed' => [
        \App\Services\Events\Handlers\MatchCompletedEventHandler::class,
    ],
    'sports.tournament.status.changed' => [
        \App\Services\Events\Handlers\TournamentEventHandler::class,
    ],
],
```

**Error Handling:**
```php
'error_handling' => [
    'max_retry_attempts' => 3,
    'retry_delay_ms' => 1000,
    'dead_letter_queue' => 'events.dlq',
    'alert_on_failures' => false,
],
```

## Usage

### Start Event Listener

```bash
# Start listening to all configured channels
php artisan events:listen

# Listen to specific channels
php artisan events:listen --channels=sports.match.completed
```

### Running as Daemon (Production)

Use supervisor or systemd to run the command as a daemon:

**Supervisor Config:**
```ini
[program:results-service-events]
command=php /path/to/artisan events:listen
directory=/path/to/results-service
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/results-service-events.log
```

## Idempotency

Events are tracked using Redis keys:
- `events:processed:{event_id}` - Marks event as processed (30 day TTL)
- `events:processing:{event_id}` - Marks event as currently processing (5 min TTL)

This prevents:
- Duplicate standings updates
- Race conditions
- Event replay issues

## Dead Letter Queue

Failed events (after all retry attempts) are sent to:
- Redis List: `events.dlq`
- Format: JSON with original event + failure reason

**Manual Processing:**
```bash
# View DLQ events
redis-cli LRANGE events.dlq 0 -1

# Process manually
# Extract event from DLQ and reprocess
```

## Event Payload Structure

### Match Completed Event (from match-service)
```json
{
  "event_id": "uuid",
  "event_type": "sports.match.completed",
  "service": "match-service",
  "payload": {
    "match_id": 123,
    "tournament_id": 456,
    "home_team_id": 1,
    "away_team_id": 2,
    "home_score": 2,
    "away_score": 1,
    "completed_at": "2024-01-01T12:00:00Z"
  },
  "timestamp": "2024-01-01T12:00:00Z",
  "version": "1.0"
}
```

### Standings Updated Event (published by results-service)
```json
{
  "event_id": "uuid",
  "event_type": "sports.standings.updated",
  "service": "results-service",
  "payload": {
    "tournament_id": 456,
    "match_id": 123,
    "home_team_id": 1,
    "away_team_id": 2,
    "updated_at": "2024-01-01T12:00:00Z"
  },
  "timestamp": "2024-01-01T12:00:00Z",
  "version": "1.0"
}
```

## Testing

### Manual Testing

1. **Publish test event:**
```bash
redis-cli PUBLISH sports.match.completed '{
  "event_id": "test-uuid-123",
  "event_type": "sports.match.completed",
  "service": "match-service",
  "payload": {
    "match_id": 1,
    "tournament_id": 1,
    "home_team_id": 1,
    "away_team_id": 2,
    "home_score": 2,
    "away_score": 1,
    "completed_at": "2024-01-01T12:00:00Z"
  },
  "timestamp": "2024-01-01T12:00:00Z",
  "version": "1.0"
}'
```

2. **Check logs:**
```bash
tail -f storage/logs/laravel.log | grep "Match completed event"
```

3. **Verify standings updated:**
```bash
# Check database
mysql -e "SELECT * FROM standings WHERE tournament_id = 1;"
```

## Monitoring

### Key Metrics to Monitor

1. **Event Processing Rate**
   - Events processed per minute
   - Events failed per minute

2. **Processing Time**
   - Average time to process match completed event
   - Standings calculation time

3. **Error Rate**
   - Failed events / Total events
   - Dead letter queue size

4. **Idempotency**
   - Duplicate events detected
   - Events already processed

### Log Queries

```bash
# Count processed events
grep "Match completed event processed successfully" storage/logs/laravel.log | wc -l

# Count failed events
grep "All retry attempts failed" storage/logs/laravel.log | wc -l

# Check dead letter queue size
redis-cli LLEN events.dlq
```

## Troubleshooting

### Event Not Processing

1. **Check listener is running:**
```bash
ps aux | grep "events:listen"
```

2. **Check Redis connection:**
```bash
redis-cli PING
```

3. **Check logs:**
```bash
tail -f storage/logs/laravel.log
```

### Duplicate Processing

1. **Check idempotency keys:**
```bash
redis-cli KEYS "events:processed:*"
```

2. **Clear if needed (careful!):**
```bash
redis-cli DEL events:processed:{event_id}
```

### Dead Letter Queue Growing

1. **View failed events:**
```bash
redis-cli LRANGE events.dlq 0 10
```

2. **Investigate failure reasons**
3. **Reprocess manually if needed**

## Integration with Other Services

### Match Service
- Publishes: `sports.match.completed`
- Results Service subscribes and processes

### Tournament Service
- Publishes: `sports.tournament.status.changed`
- Results Service triggers full recalculation

### Gateway Service
- Can subscribe to: `sports.standings.updated`
- For cache invalidation

## Future Enhancements

1. **Event Sourcing**: Store all events for replay
2. **Metrics Collection**: Prometheus metrics
3. **Distributed Tracing**: OpenTelemetry support
4. **Message Queue**: Migrate from Redis Pub/Sub to RabbitMQ/Kafka
5. **Event Versioning**: Support multiple event versions
6. **Saga Pattern**: For distributed transactions
