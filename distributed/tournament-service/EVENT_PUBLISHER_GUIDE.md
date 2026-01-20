# EventPublisher Integration Guide

## EventPublisher Service

The EventPublisher service uses Redis Pub/Sub to publish events with the following format:

```json
{
    "type": "tournament.created",
    "payload": {
        "tournament": {...},
        "action": "created",
        "timestamp": "2026-01-20T12:00:00.000000Z"
    },
    "timestamp": "2026-01-20T12:00:00.000000Z",
    "service": "tournament-service",
    "version": "1.0",
    "id": "event_1234567890abcdef"
}
```

## Available Events

1. **tournament.created** - When a new tournament is created
2. **tournament.updated** - When a tournament is updated
3. **tournament.status.changed** - When tournament status changes

## Integration in TournamentController

### 1. Constructor Injection

```php
use App\Services\EventPublisher;

class TournamentController extends Controller
{
    protected AuthService $authService;
    protected EventPublisher $eventPublisher;

    public function __construct(AuthService $authService, EventPublisher $eventPublisher)
    {
        $this->authService = $authService;
        $this->eventPublisher = $eventPublisher;
    }
```

### 2. Publish on Tournament Creation

```php
public function store(Request $request): JsonResponse
{
    try {
        // ... validation and authentication ...
        
        $tournament = Tournament::create($validated);

        // Publish tournament created event
        $this->eventPublisher->publishTournamentCreated(
            $tournament->load(['sport', 'settings'])->toArray()
        );

        Log::info('Tournament created successfully', [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'user_id' => $user['id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tournament created successfully',
            'data' => $tournament->load(['sport', 'settings'])
        ], 201);
    } catch (\Exception $e) {
        // ... error handling ...
    }
}
```

### 3. Publish on Tournament Update

```php
public function update(Request $request, string $id): JsonResponse
{
    try {
        // ... authentication and validation ...
        
        $tournament = Tournament::find($id);
        $oldData = $tournament->toArray();
        
        $tournament->update($validated);

        // Publish tournament updated event
        $this->eventPublisher->publishTournamentUpdated(
            $tournament->load(['sport', 'settings'])->toArray(),
            $oldData
        );

        Log::info('Tournament updated successfully', [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'user_id' => $user['id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tournament updated successfully',
            'data' => $tournament->load(['sport', 'settings'])
        ]);
    } catch (\Exception $e) {
        // ... error handling ...
    }
}
```

### 4. Publish on Status Change

```php
public function updateStatus(Request $request, string $id): JsonResponse
{
    try {
        // ... authentication and validation ...
        
        $tournament = Tournament::find($id);
        $oldStatus = $tournament->status;
        
        $tournament->update(['status' => $request->status]);

        // Publish tournament status changed event
        $this->eventPublisher->publishTournamentStatusChanged(
            $tournament->load(['sport', 'settings'])->toArray(),
            $oldStatus
        );

        Log::info('Tournament status updated successfully', [
            'tournament_id' => $tournament->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'user_id' => $user['id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tournament status updated successfully',
            'data' => $tournament
        ]);
    } catch (\Exception $e) {
        // ... error handling ...
    }
}
```

## Configuration

### Environment Variables

```env
# Events Configuration
EVENTS_CHANNEL=sports-tournament-events
EVENTS_ENABLED=true
EVENTS_HISTORY_TTL=86400
EVENTS_MAX_HISTORY=1000
EVENTS_RETRY_MAX_ATTEMPTS=3
EVENTS_RETRY_DELAY_MS=100
```

### Redis Configuration

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_EVENTS_CHANNEL=sports-events
```

## Testing Event Publishing

### Test Script

```bash
#!/bin/bash

# Test Redis connection
redis-cli ping

# Subscribe to events channel
redis-cli subscribe sports-tournament-events

# In another terminal, create a tournament to see events
curl -X POST http://localhost:8002/api/tournaments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sport_id":1,"name":"Test Tournament","start_date":"2026-06-01","end_date":"2026-07-01"}'
```

### Expected Event Output

```json
{
    "type": "tournament.created",
    "payload": {
        "tournament": {
            "id": 1,
            "name": "Test Tournament",
            "sport_id": 1,
            "status": "planned",
            "created_at": "2026-01-20T12:00:00.000000Z"
        },
        "action": "created",
        "timestamp": "2026-01-20T12:00:00.000000Z"
    },
    "timestamp": "2026-01-20T12:00:00.000000Z",
    "service": "tournament-service",
    "version": "1.0",
    "id": "event_67890abcdef12345"
}
```

## Error Handling

The EventPublisher automatically handles errors:

1. **Redis Connection Errors**: Logs error and returns false
2. **JSON Encoding Errors**: Logs error and returns false
3. **Publishing Failures**: Logs error with subscriber count

```php
$published = $this->eventPublisher->publishTournamentCreated($tournamentData);

if (!$published) {
    // Handle publishing failure
    Log::warning('Failed to publish tournament created event');
}
```

## Logging

All events are logged with:

- Event type and payload keys
- Service name and channel
- Number of subscribers notified
- Timestamp and unique event ID

Example log entry:
```
[2026-01-20 12:00:00] local.INFO: Event published 
{
    "event_type": "tournament.created",
    "service": "tournament-service", 
    "channel": "sports-tournament-events",
    "subscribers_notified": 3,
    "payload_keys": ["tournament", "action", "timestamp"],
    "timestamp": "2026-01-20T12:00:00.000000Z"
}
```
