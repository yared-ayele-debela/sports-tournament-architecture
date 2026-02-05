# Tournament Service

Tournament Management Service for the Sports Tournament Architecture. This service handles tournament creation, sports management, venue management, and provides comprehensive tournament data.

## Features

- **Tournament Management**: Complete CRUD operations for tournaments
- **Sports Management**: Manage different sports types
- **Venue Management**: Manage tournament venues
- **Tournament Settings**: Configure tournament-specific settings
- **Status Management**: Update tournament status (draft, published, ongoing, completed)
- **Tournament Overview**: Comprehensive tournament data aggregation
- **Event-Driven**: Publishes events for integration with other services
- **Cache Management**: Intelligent caching with automatic invalidation
- **RESTful API**: Standardized JSON responses with consistent error handling
- **Public API**: Public endpoints for tournament information
- **Health Monitoring**: Health check endpoints for service monitoring

## Technology Stack

- **Framework**: Laravel 11
- **Authentication**: Laravel Passport (OAuth2) for protected endpoints
- **Database**: MySQL/PostgreSQL (configurable)
- **Queue**: Redis/RabbitMQ (for event publishing)
- **Cache**: Redis

## API Endpoints

### Base URL
```
http://localhost:8002/api
```

### Public Endpoints (No Authentication Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check endpoint |
| GET | `/health/info` | Service information |
| GET | `/tournaments` | List all tournaments |
| GET | `/tournaments/{id}` | Get tournament details |
| GET | `/tournaments/{id}/matches` | Get tournament matches |
| GET | `/tournaments/{id}/teams` | Get tournament teams |
| GET | `/tournaments/{id}/overview` | Get tournament overview |
| GET | `/tournaments/{id}/statistics` | Get tournament statistics |
| GET | `/tournaments/{id}/standings` | Get tournament standings |

### Protected Endpoints (Requires Bearer Token)

#### Sports Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sports` | List all sports |
| POST | `/sports` | Create a new sport |
| GET | `/sports/{id}` | Get sport details |
| PUT | `/sports/{id}` | Update sport |
| DELETE | `/sports/{id}` | Delete sport |

**Request Body (Create/Update):**
```json
{
    "name": "Football",
    "description": "Association football",
    "min_players": 11,
    "max_players": 11
}
```

#### Tournament Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/tournaments` | Create a new tournament |
| PUT | `/tournaments/{id}` | Update tournament |
| DELETE | `/tournaments/{id}` | Delete tournament |
| PATCH | `/tournaments/{id}/status` | Update tournament status |
| GET | `/tournaments/{id}/validate` | Validate tournament existence |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by tournament name
- `sport_id` (optional): Filter by sport ID
- `status` (optional): Filter by status

**Request Body (Create/Update):**
```json
{
    "name": "Premier League 2024",
    "sport_id": 1,
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "format": "league",
    "description": "Annual premier league tournament"
}
```

**Request Body (Update Status):**
```json
{
    "status": "published"
}
```

**Tournament Statuses:**
- `draft`: Tournament is being created
- `published`: Tournament is published and visible
- `ongoing`: Tournament is currently active
- `completed`: Tournament has finished
- `cancelled`: Tournament was cancelled

**Tournament Formats:**
- `league`: Round-robin league format
- `knockout`: Single or double elimination
- `group_stage`: Group stage followed by knockout
- `round_robin`: Round-robin format

#### Tournament Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tournaments/{id}/settings` | Get tournament settings |
| POST | `/tournaments/{id}/settings` | Create/update tournament settings |

**Request Body (Create/Update Settings):**
```json
{
    "points_per_win": 3,
    "points_per_draw": 1,
    "points_per_loss": 0,
    "max_teams": 20,
    "min_teams": 2,
    "allow_draws": true,
    "extra_time_enabled": false,
    "penalty_shootout_enabled": false
}
```

#### Venue Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/venues` | List all venues |
| POST | `/venues` | Create a new venue |
| GET | `/venues/{id}` | Get venue details |
| PUT | `/venues/{id}` | Update venue |
| DELETE | `/venues/{id}` | Delete venue |

**Request Body (Create/Update):**
```json
{
    "name": "Stadium Name",
    "address": "123 Stadium Street",
    "city": "City Name",
    "country": "Country Name",
    "capacity": 50000,
    "surface_type": "grass"
}
```

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Tournament created successfully",
    "data": {
        "id": 1,
        "name": "Premier League 2024",
        "sport_id": 1,
        "start_date": "2024-01-01",
        "end_date": "2024-12-31",
        "format": "league",
        "status": "draft",
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
        "name": ["The name field is required."]
    },
    "error_code": "VALIDATION_ERROR",
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Tournaments retrieved successfully",
    "data": [
        // Array of tournaments
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

### Tournament Overview Response
```json
{
    "success": true,
    "message": "Tournament overview retrieved successfully",
    "data": {
        "id": 1,
        "name": "Premier League 2024",
        "sport": {
            "id": 1,
            "name": "Football"
        },
        "start_date": "2024-01-01",
        "end_date": "2024-12-31",
        "format": "league",
        "status": "ongoing",
        "teams_count": 20,
        "matches_count": 190,
        "completed_matches": 100,
        "upcoming_matches": 90,
        "standings": [
            // Standings data
        ],
        "top_scorers": [
            // Top scorers data
        ]
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
cd tournament-service
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
DB_DATABASE=tournament_service
DB_USERNAME=root
DB_PASSWORD=
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Install Passport (for authentication)**
```bash
php artisan passport:install
```

8. **Start the development server**
```bash
php artisan serve --port=8002
```

The service will be available at `http://localhost:8002`

## Environment Variables

Key environment variables to configure:

```env
APP_NAME="Tournament Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8002

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tournament_service
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis

# Passport Configuration
PASSPORT_CLIENT_ID=
PASSPORT_CLIENT_SECRET=

# Service URLs (for inter-service communication)
AUTH_SERVICE_URL=http://localhost:8000
MATCH_SERVICE_URL=http://localhost:8003
TEAM_SERVICE_URL=http://localhost:8004
RESULTS_SERVICE_URL=http://localhost:8005
```

## Database Schema

### Sports Table
- `id`: Primary key
- `name`: Sport name
- `description`: Sport description
- `min_players`: Minimum players required
- `max_players`: Maximum players allowed
- `created_at`, `updated_at`: Timestamps

### Tournaments Table
- `id`: Primary key
- `name`: Tournament name
- `sport_id`: Foreign key to sports
- `start_date`: Tournament start date
- `end_date`: Tournament end date
- `format`: Tournament format (league, knockout, etc.)
- `status`: Tournament status
- `description`: Tournament description
- `created_at`, `updated_at`: Timestamps

### Tournament Settings Table
- `id`: Primary key
- `tournament_id`: Foreign key to tournaments
- `points_per_win`: Points awarded for a win
- `points_per_draw`: Points awarded for a draw
- `points_per_loss`: Points awarded for a loss
- `max_teams`: Maximum number of teams
- `min_teams`: Minimum number of teams
- `allow_draws`: Whether draws are allowed
- `extra_time_enabled`: Whether extra time is enabled
- `penalty_shootout_enabled`: Whether penalty shootouts are enabled
- Additional settings fields
- `created_at`, `updated_at`: Timestamps

### Venues Table
- `id`: Primary key
- `name`: Venue name
- `address`: Venue address
- `city`: City name
- `country`: Country name
- `capacity`: Venue capacity
- `surface_type`: Surface type (grass, artificial, etc.)
- `created_at`, `updated_at`: Timestamps

## Event Publishing

The service publishes events to a message queue for other services:

- `tournament.created`: When a new tournament is created
- `tournament.updated`: When a tournament is updated
- `tournament.status.changed`: When tournament status changes
- `tournament.deleted`: When a tournament is deleted
- `sport.created`: When a new sport is created
- `sport.updated`: When a sport is updated
- `venue.created`: When a new venue is created
- `venue.updated`: When a venue is updated

## Usage Examples

### Create a Tournament
```bash
curl -X POST http://localhost:8002/api/tournaments \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Premier League 2024",
    "sport_id": 1,
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "format": "league",
    "description": "Annual premier league tournament"
  }'
```

### Get Tournament Details (Public)
```bash
curl -X GET http://localhost:8002/api/tournaments/1
```

### Get Tournament Overview
```bash
curl -X GET http://localhost:8002/api/tournaments/1/overview
```

### Create a Sport
```bash
curl -X POST http://localhost:8002/api/sports \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Football",
    "description": "Association football",
    "min_players": 11,
    "max_players": 11
  }'
```

### Create a Venue
```bash
curl -X POST http://localhost:8002/api/venues \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Stadium Name",
    "address": "123 Stadium Street",
    "city": "City Name",
    "country": "Country Name",
    "capacity": 50000,
    "surface_type": "grass"
  }'
```

### Update Tournament Status
```bash
curl -X PATCH http://localhost:8002/api/tournaments/1/status \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published"
  }'
```

### Configure Tournament Settings
```bash
curl -X POST http://localhost:8002/api/tournaments/1/settings \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "points_per_win": 3,
    "points_per_draw": 1,
    "points_per_loss": 0,
    "max_teams": 20,
    "min_teams": 2,
    "allow_draws": true
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
| `TOURNAMENT_NOT_FOUND` | 404 | Tournament not found |
| `TOURNAMENT_ALREADY_STARTED` | 400 | Tournament has already started |
| `TOURNAMENT_INVALID_STATE` | 400 | Tournament is in an invalid state for this operation |
| `TOURNAMENT_ALREADY_EXISTS` | 422 | Tournament with this name already exists |
| `SPORT_NOT_FOUND` | 404 | Sport not found |
| `VENUE_NOT_FOUND` | 404 | Venue not found |

For a complete list of error codes, see [ERROR_CODES.md](../ERROR_CODES.md).
| `INVALID_STATUS` | Invalid tournament status |
| `INTERNAL_SERVER_ERROR` | Server error occurred |

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
supervisorctl start tournament-service-queue:*
```

## Contributing

1. Follow the existing code structure
2. Write tests for new features
3. Update documentation
4. Follow PSR-12 coding standards

## License

This service is part of the Sports Tournament Architecture project.
