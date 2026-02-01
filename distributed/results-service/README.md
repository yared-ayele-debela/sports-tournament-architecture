# Results Service

Results and Statistics Service for the Sports Tournament Architecture. This service handles tournament standings, match results, team statistics, and provides comprehensive statistical analysis.

## Features

- **Standings Management**: Calculate and manage tournament standings
- **Match Results**: Finalize match results and update standings
- **Statistics**: Comprehensive statistics for tournaments and teams
- **Top Scorers**: Track and calculate top goal scorers
- **Real-time Updates**: Automatic standings recalculation on match completion
- **Event-Driven**: Consumes match events for automatic updates
- **Cache Management**: Intelligent caching with automatic invalidation
- **RESTful API**: Standardized JSON responses with consistent error handling
- **Health Monitoring**: Health check endpoints for service monitoring

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL/PostgreSQL (configurable)
- **Queue**: Redis/RabbitMQ (for event consumption)
- **Cache**: Redis

## API Endpoints

### Base URL
```
http://localhost:8005/api/v1
```

### Public Endpoints (No Authentication Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check endpoint |
| GET | `/tournaments/{tournamentId}/standings` | Get tournament standings |
| GET | `/tournaments/{tournamentId}/statistics` | Get tournament statistics |

### Protected Endpoints (Requires Service Token)

#### Standings Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/standings/recalculate/{tournamentId}` | Manually recalculate tournament standings |

#### Match Results

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tournaments/{tournamentId}/results` | Get all match results for a tournament |
| GET | `/results/{id}` | Get specific match result |
| POST | `/matches/{matchId}/finalize` | Finalize match result and update standings |

**Request Body (Finalize Match):**
```json
{
    "home_score": 2,
    "away_score": 1,
    "home_team_possession": 55,
    "away_team_possession": 45,
    "home_team_shots": 12,
    "away_team_shots": 8
}
```

#### Statistics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/teams/{teamId}/statistics` | Get team statistics |

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Standings retrieved successfully",
    "data": {
        "tournament_id": 1,
        "standings": [
            {
                "team_id": 1,
                "team_name": "Team A",
                "played": 5,
                "won": 3,
                "drawn": 1,
                "lost": 1,
                "goals_for": 10,
                "goals_against": 5,
                "goal_difference": 5,
                "points": 10,
                "position": 1
            }
        ],
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Tournament not found",
    "error_code": "RESOURCE_NOT_FOUND",
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Statistics Response
```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "team_id": 1,
        "team_name": "Team A",
        "tournament_id": 1,
        "matches_played": 5,
        "matches_won": 3,
        "matches_drawn": 1,
        "matches_lost": 1,
        "goals_for": 10,
        "goals_against": 5,
        "goal_difference": 5,
        "points": 10,
        "average_goals_per_match": 2.0,
        "win_percentage": 60.0,
        "clean_sheets": 2,
        "top_scorer": {
            "player_id": 1,
            "player_name": "John Doe",
            "goals": 5
        }
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
cd results-service
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
DB_DATABASE=results_service
DB_USERNAME=root
DB_PASSWORD=
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Start the development server**
```bash
php artisan serve --port=8005
```

The service will be available at `http://localhost:8005`

## Environment Variables

Key environment variables to configure:

```env
APP_NAME="Results Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8005

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=results_service
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis

# Service URLs (for inter-service communication)
TOURNAMENT_SERVICE_URL=http://localhost:8002
MATCH_SERVICE_URL=http://localhost:8003
TEAM_SERVICE_URL=http://localhost:8004
```

## Database Schema

### Standings Table
- `id`: Primary key
- `tournament_id`: Foreign key to tournaments
- `team_id`: Foreign key to teams
- `played`: Number of matches played
- `won`: Number of matches won
- `drawn`: Number of matches drawn
- `lost`: Number of matches lost
- `goals_for`: Goals scored
- `goals_against`: Goals conceded
- `goal_difference`: Goal difference
- `points`: Total points
- `position`: Current position in standings
- `created_at`, `updated_at`: Timestamps

### Match Results Table
- `id`: Primary key
- `match_id`: Foreign key to matches
- `tournament_id`: Foreign key to tournaments
- `home_team_id`: Foreign key to teams
- `away_team_id`: Foreign key to teams
- `home_score`: Home team final score
- `away_score`: Away team final score
- `home_team_possession`: Home team possession percentage
- `away_team_possession`: Away team possession percentage
- Additional match statistics
- `finalized_at`: When the result was finalized
- `created_at`, `updated_at`: Timestamps

### Statistics Table
- `id`: Primary key
- `tournament_id`: Foreign key to tournaments
- `team_id`: Foreign key to teams
- `player_id`: Foreign key to players (for player statistics)
- Various statistical fields
- `created_at`, `updated_at`: Timestamps

## Event Consumption

The service consumes events from other services:

- `match.completed`: Automatically recalculates standings when a match is completed
- `match.event.created`: Updates statistics when match events are created
- `match.result.finalized`: Processes finalized match results
- `tournament.*`: Tournament-related events for cache invalidation

## Standings Calculation

Standings are calculated based on:

- **Points System**: 
  - Win: 3 points
  - Draw: 1 point
  - Loss: 0 points
- **Tie-breaking Rules**:
  1. Points
  2. Goal difference
  3. Goals scored
  4. Head-to-head record
  5. Fair play points

## Usage Examples

### Get Tournament Standings
```bash
curl -X GET http://localhost:8005/api/v1/tournaments/1/standings
```

### Finalize Match Result
```bash
curl -X POST http://localhost:8005/api/v1/matches/1/finalize \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "home_score": 2,
    "away_score": 1,
    "home_team_possession": 55,
    "away_team_possession": 45,
    "home_team_shots": 12,
    "away_team_shots": 8
  }'
```

### Get Tournament Statistics
```bash
curl -X GET http://localhost:8005/api/v1/tournaments/1/statistics
```

### Get Team Statistics
```bash
curl -X GET http://localhost:8005/api/v1/teams/1/statistics \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN"
```

### Recalculate Standings
```bash
curl -X POST http://localhost:8005/api/v1/standings/recalculate/1 \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN"
```

## Cache Strategy

- **Standings**: Cached for 1 minute (invalidated on match completion)
- **Statistics**: Cached for 5 minutes
- **Match Results**: Cached for 10 minutes

Cache is automatically invalidated when:
- A match is completed
- A match result is finalized
- Standings are recalculated

## Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `UNAUTHORIZED` | Authentication required |
| `TOURNAMENT_NOT_FOUND` | Tournament not found |
| `MATCH_NOT_FOUND` | Match not found |
| `MATCH_ALREADY_FINALIZED` | Match result already finalized |
| `INTERNAL_SERVER_ERROR` | Server error occurred |

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
Follow PSR-12 coding standards.

### Queue Workers
If using queues for event consumption, start the worker:
```bash
php artisan queue:work
```

Or use supervisor for production:
```bash
supervisorctl start results-service-queue:*
```

## Contributing

1. Follow the existing code structure
2. Write tests for new features
3. Update documentation
4. Follow PSR-12 coding standards

## License

This service is part of the Sports Tournament Architecture project.
