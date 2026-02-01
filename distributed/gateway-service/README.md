# Gateway Service

API Gateway Service for the Sports Tournament Architecture. This service acts as a unified entry point for public-facing APIs, aggregating data from multiple microservices and providing a consistent interface for clients.

## Features

- **Public API Aggregation**: Unified interface for accessing tournament, match, team, and standings data
- **Data Aggregation**: Combines data from multiple microservices (tournament, match, team, results services)
- **Search Functionality**: Global search across tournaments, teams, and matches
- **Rate Limiting**: Configurable rate limits for different endpoint types
- **Caching**: Intelligent caching for improved performance
- **Event-Driven**: Subscribes to events from other services for real-time updates
- **Health Monitoring**: Comprehensive health check endpoints
- **RESTful API**: Standardized JSON responses with consistent error handling

## Technology Stack

- **Framework**: Laravel 12
- **HTTP Client**: Guzzle HTTP
- **Cache**: Redis
- **Queue**: Redis (for event processing)

## API Endpoints

### Base URL
```
http://localhost:8001/api/public
```

### Public Endpoints

#### Homepage Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/featured` | Get featured tournaments and matches |
| GET | `/tournaments/upcoming` | Get upcoming tournaments statistics |

#### Tournament Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tournaments` | List all tournaments |
| GET | `/tournaments/{id}` | Get tournament details |
| GET | `/tournaments/{id}/standings` | Get tournament standings |
| GET | `/tournaments/{id}/matches` | Get tournament matches |
| GET | `/tournaments/{id}/statistics` | Get tournament statistics |
| GET | `/tournaments/{id}/overview` | Get tournament overview |
| GET | `/tournaments/{id}/teams` | Get tournament teams |
| GET | `/tournaments/{id}/top-scorers` | Get tournament top scorers |

#### Match Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/matches` | List all matches |
| GET | `/matches/live` | Get live matches |
| GET | `/matches/upcoming` | Get upcoming matches |
| GET | `/matches/completed` | Get completed matches |
| GET | `/matches/date/{date}` | Get matches by date (YYYY-MM-DD) |
| GET | `/matches/{id}` | Get match details |
| GET | `/matches/{id}/events` | Get match events |

#### Team Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/teams/{id}` | Get team profile |
| GET | `/teams/{id}/overview` | Get team overview |
| GET | `/teams/{id}/squad` | Get team squad/players |
| GET | `/teams/{id}/matches` | Get team matches |
| GET | `/teams/{id}/statistics` | Get team statistics |

#### Standings Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/standings/tournament/{tournamentId}` | Get tournament standings |
| GET | `/standings/tournament/{tournamentId}/with-teams` | Get standings with team details |
| GET | `/standings/tournament/{tournamentId}/statistics` | Get standings statistics |
| GET | `/standings/tournament/{tournamentId}/top-scorers` | Get top scorers |

#### Search Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/search?q={query}` | Global search across tournaments, teams, and matches |

#### Health & Documentation

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check endpoint |
| GET | `/docs` | API documentation |

### Rate Limits

- **Public API**: 60 requests per minute per IP
- **Search**: 20 requests per minute per IP
- **Live Matches**: 120 requests per minute per IP

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": {
        // Resource data
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Resource not found",
    "error_code": "RESOURCE_NOT_FOUND",
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": [
        // Array of items
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
- Redis (for caching and queues)

### Installation

1. **Clone and navigate to the service directory**
```bash
cd gateway-service
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

5. **Configure services in `.env`**
```env
TOURNAMENT_SERVICE_URL=http://localhost:8002
MATCH_SERVICE_URL=http://localhost:8003
TEAM_SERVICE_URL=http://localhost:8004
RESULTS_SERVICE_URL=http://localhost:8005
```

6. **Configure Redis in `.env`**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
```

7. **Start the development server**
```bash
php artisan serve --port=8001
```

The service will be available at `http://localhost:8001`

## Environment Variables

Key environment variables to configure:

```env
APP_NAME="Gateway Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

# Service URLs
TOURNAMENT_SERVICE_URL=http://localhost:8002
MATCH_SERVICE_URL=http://localhost:8003
TEAM_SERVICE_URL=http://localhost:8004
RESULTS_SERVICE_URL=http://localhost:8005

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Service Dependencies

This service depends on the following microservices:

- **Tournament Service**: Tournament data and management
- **Match Service**: Match data and events
- **Team Service**: Team and player information
- **Results Service**: Standings, results, and statistics

## Event Subscription

The gateway service subscribes to events from other services for real-time cache invalidation and data updates:

- `tournament.*`: Tournament-related events
- `match.*`: Match-related events
- `team.*`: Team-related events
- `result.*`: Result and standings events

## Usage Examples

### Get Featured Content
```bash
curl -X GET http://localhost:8001/api/public/featured
```

### Get Tournament Details
```bash
curl -X GET http://localhost:8001/api/public/tournaments/1
```

### Get Live Matches
```bash
curl -X GET http://localhost:8001/api/public/matches/live
```

### Search
```bash
curl -X GET "http://localhost:8001/api/public/search?q=premier"
```

### Get Team Profile
```bash
curl -X GET http://localhost:8001/api/public/teams/1
```

## Caching Strategy

- **Tournament Data**: Cached for 5 minutes
- **Match Data**: Cached for 2 minutes (live matches: 30 seconds)
- **Team Data**: Cached for 10 minutes
- **Standings**: Cached for 1 minute
- **Search Results**: Cached for 5 minutes

Cache is automatically invalidated when events are received from source services.

## Error Codes

| Code | Description |
|------|-------------|
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `SERVICE_UNAVAILABLE` | Upstream service unavailable |
| `RATE_LIMIT_EXCEEDED` | Rate limit exceeded |
| `VALIDATION_ERROR` | Request validation failed |
| `INTERNAL_SERVER_ERROR` | Server error occurred |

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
Follow PSR-12 coding standards.

### Queue Workers
If using queues for event processing, start the worker:
```bash
php artisan queue:work
```

## Contributing

1. Follow the existing code structure
2. Write tests for new features
3. Update documentation
4. Follow PSR-12 coding standards

## License

This service is part of the Sports Tournament Architecture project.
