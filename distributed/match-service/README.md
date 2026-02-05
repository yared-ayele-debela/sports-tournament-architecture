# Match Service

Match Management Service for the Sports Tournament Architecture. This service handles match creation, scheduling, live updates, events tracking, and match reports.

## Features

- **Match Management**: Complete CRUD operations for matches
- **Match Scheduling**: Automatic schedule generation for tournaments
- **Match Events**: Track goals, cards, substitutions, and other match events
- **Live Match Updates**: Real-time match status and event tracking
- **Match Reports**: Generate and manage match reports
- **Status Management**: Update match status (scheduled, live, completed, cancelled)
- **Event-Driven**: Publishes and consumes events for integration with other services
- **Cache Management**: Intelligent caching with automatic invalidation
- **RESTful API**: Standardized JSON responses with consistent error handling
- **Health Monitoring**: Health check endpoints for service monitoring

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL/PostgreSQL (configurable)
- **Queue**: Redis/RabbitMQ (for event publishing and consumption)
- **Cache**: Redis

## API Endpoints

### Base URL
```
http://localhost:8003/api/v1
```

### Public Endpoints (No Authentication Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check endpoint |
| GET | `/public/tournaments/{tournamentId}/matches` | Get tournament matches |
| GET | `/public/matches` | List all matches |
| GET | `/public/matches/live` | Get live matches |
| GET | `/public/matches/upcoming` | Get upcoming matches |
| GET | `/public/matches/completed` | Get completed matches |
| GET | `/public/matches/date/{date}` | Get matches by date (YYYY-MM-DD) |
| GET | `/public/matches/{id}` | Get match details |
| GET | `/public/matches/{id}/events` | Get match events |

### Protected Endpoints (Requires Service Token)

#### Match Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/matches` | List all matches (with pagination and filters) |
| POST | `/matches` | Create a new match |
| GET | `/matches/{id}` | Get match details |
| PUT | `/matches/{id}` | Update match |
| DELETE | `/matches/{id}` | Delete match |
| PATCH | `/matches/{id}/status` | Update match status |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `tournament_id` (optional): Filter by tournament ID
- `status` (optional): Filter by status (scheduled, live, completed, cancelled)
- `date` (optional): Filter by date (YYYY-MM-DD)

**Request Body (Create/Update):**
```json
{
    "tournament_id": 1,
    "home_team_id": 1,
    "away_team_id": 2,
    "venue_id": 1,
    "scheduled_at": "2024-01-15 15:00:00",
    "status": "scheduled"
}
```

**Request Body (Update Status):**
```json
{
    "status": "live"
}
```

#### Schedule Generation

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/tournaments/{tournamentId}/generate-schedule` | Generate match schedule for tournament |

**Request Body:**
```json
{
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "matches_per_day": 2
}
```

#### Match Events

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/matches/{matchId}/events` | Get all events for a match |
| POST | `/matches/{matchId}/events` | Create a match event |
| DELETE | `/events/{id}` | Delete a match event |

**Request Body (Create Event):**
```json
{
    "type": "goal",
    "minute": 45,
    "player_id": 1,
    "team_id": 1,
    "description": "Goal scored by Player Name"
}
```

**Event Types:**
- `goal`: Goal scored
- `card_yellow`: Yellow card
- `card_red`: Red card
- `substitution`: Player substitution
- `penalty`: Penalty kick
- `own_goal`: Own goal
- `other`: Other events

#### Match Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/matches/{matchId}/report` | Get match report |
| POST | `/matches/{matchId}/report` | Create/update match report |

**Request Body (Create Report):**
```json
{
    "summary": "Match summary text",
    "home_team_possession": 55,
    "away_team_possession": 45,
    "home_team_shots": 12,
    "away_team_shots": 8,
    "home_team_shots_on_target": 6,
    "away_team_shots_on_target": 4
}
```

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Match created successfully",
    "data": {
        "id": 1,
        "tournament_id": 1,
        "home_team_id": 1,
        "away_team_id": 2,
        "status": "scheduled",
        "scheduled_at": "2024-01-15T15:00:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "tournament_id": ["The tournament id field is required."]
    },
    "error_code": "VALIDATION_ERROR",
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Matches retrieved successfully",
    "data": [
        // Array of matches
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "from": 1,
        "to": 15
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL/PostgreSQL
- Redis (for queues and cache)

### Installation

1. **Clone and navigate to the service directory**
```bash
cd match-service
```

2. **Install dependencies**
```bash
composer install
```

3. **Copy environment file**
```bash
cp .env.example .env
```

4. **Generate application key**
```bash
php artisan key:generate
```

5. **Configure database in `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=match_service
DB_USERNAME=root
DB_PASSWORD=
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Start the development server**
```bash
php artisan serve --port=8003
```

The service will be available at `http://localhost:8003`

## Environment Variables

Key environment variables to configure:

```env
APP_NAME="Match Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8003

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=match_service
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis

# Service URLs (for inter-service communication)
TOURNAMENT_SERVICE_URL=http://localhost:8002
TEAM_SERVICE_URL=http://localhost:8004
RESULTS_SERVICE_URL=http://localhost:8005
```

## Database Schema

### Matches Table
- `id`: Primary key
- `tournament_id`: Foreign key to tournaments
- `home_team_id`: Foreign key to teams
- `away_team_id`: Foreign key to teams
- `venue_id`: Foreign key to venues
- `scheduled_at`: Match scheduled date and time
- `status`: Match status (scheduled, live, completed, cancelled)
- `home_score`: Home team score
- `away_score`: Away team score
- `created_at`, `updated_at`: Timestamps

### Match Events Table
- `id`: Primary key
- `match_id`: Foreign key to matches
- `type`: Event type (goal, card, substitution, etc.)
- `minute`: Minute when event occurred
- `player_id`: Foreign key to players
- `team_id`: Foreign key to teams
- `description`: Event description
- `created_at`, `updated_at`: Timestamps

### Match Reports Table
- `id`: Primary key
- `match_id`: Foreign key to matches
- `summary`: Match summary text
- `home_team_possession`: Home team possession percentage
- `away_team_possession`: Away team possession percentage
- `home_team_shots`: Home team total shots
- `away_team_shots`: Away team total shots
- Additional statistics fields
- `created_at`, `updated_at`: Timestamps

## Event Publishing

The service publishes events to a message queue for other services:

- `match.created`: When a new match is created
- `match.updated`: When a match is updated
- `match.status.changed`: When match status changes
- `match.event.created`: When a match event is created
- `match.completed`: When a match is completed
- `match.schedule.generated`: When a schedule is generated

## Event Consumption

The service consumes events from other services:

- `tournament.*`: Tournament-related events
- `team.*`: Team-related events

## Usage Examples

### Create a Match
```bash
curl -X POST http://localhost:8003/api/v1/matches \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tournament_id": 1,
    "home_team_id": 1,
    "away_team_id": 2,
    "venue_id": 1,
    "scheduled_at": "2024-01-15 15:00:00",
    "status": "scheduled"
  }'
```

### Get Live Matches
```bash
curl -X GET http://localhost:8003/api/v1/public/matches/live
```

### Update Match Status
```bash
curl -X PATCH http://localhost:8003/api/v1/matches/1/status \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "live"
  }'
```

### Add Match Event
```bash
curl -X POST http://localhost:8003/api/v1/matches/1/events \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "goal",
    "minute": 45,
    "player_id": 1,
    "team_id": 1,
    "description": "Goal scored"
  }'
```

### Generate Schedule
```bash
curl -X POST http://localhost:8003/api/v1/tournaments/1/generate-schedule \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "matches_per_day": 2
  }'
```

## Error Responses

All error responses follow a standardized format:

```json
{
    "success": false,
    "message": "Error message",
    "error_code": "ERROR_CODE",
    "errors": { /* optional validation errors */ },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Common Error Codes

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| `BAD_REQUEST` | 400 | Invalid request |
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Access denied |
| `NOT_FOUND` | 404 | Resource not found |
| `RESOURCE_NOT_FOUND` | 404 | Resource not found (alternative) |
| `METHOD_NOT_ALLOWED` | 405 | HTTP method not allowed |
| `VALIDATION_ERROR` | 422 | Validation failed |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_SERVER_ERROR` | 500 | Server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |

### Service-Specific Error Codes

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| `MATCH_NOT_FOUND` | 404 | Match not found |
| `MATCH_ALREADY_COMPLETED` | 400 | Match has already been completed |
| `MATCH_INVALID_STATE` | 400 | Match is in an invalid state for this operation |
| `MATCH_CANNOT_BE_SCHEDULED` | 400 | Match cannot be scheduled (e.g., teams not ready) |
| `MATCH_EVENT_INVALID` | 400 | Invalid match event data |

For a complete list of error codes, see [ERROR_CODES.md](../ERROR_CODES.md).

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
Follow PSR-12 coding standards.

### Queue Workers
If using queues, start the worker:
```bash
php artisan queue:work
```

Or use supervisor for production:
```bash
supervisorctl start match-service-queue:*
```

## Contributing

1. Follow the existing code structure
2. Write tests for new features
3. Update documentation
4. Follow PSR-12 coding standards

## License

This service is part of the Sports Tournament Architecture project.
