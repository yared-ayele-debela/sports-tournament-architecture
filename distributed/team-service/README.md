# Team Service

Team and Player Management Service for the Sports Tournament Architecture. This service handles team creation, player management, squad management, and provides team-related data for tournaments.

## Features

- **Team Management**: Complete CRUD operations for teams
- **Player Management**: Complete CRUD operations for players
- **Squad Management**: Manage team squads and player assignments
- **Team Statistics**: Calculate and provide team statistics
- **Tournament Integration**: Get teams by tournament
- **Event-Driven**: Publishes and consumes events for integration with other services
- **Cache Management**: Intelligent caching with automatic invalidation
- **RESTful API**: Standardized JSON responses with consistent error handling
- **Public API**: Public endpoints for team and player information
- **Health Monitoring**: Health check endpoints for service monitoring

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL/PostgreSQL (configurable)
- **Queue**: Redis/RabbitMQ (for event publishing and consumption)
- **Cache**: Redis

## API Endpoints

### Base URL
```
http://localhost:8004/api/v1
```

### Public Endpoints (No Authentication Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check endpoint |
| GET | `/public/teams/{id}` | Get team profile |
| GET | `/public/teams/{id}/overview` | Get team overview |
| GET | `/public/teams/{id}/squad` | Get team squad/players |
| GET | `/public/teams/{id}/matches` | Get team matches |
| GET | `/public/teams/{id}/statistics` | Get team statistics |
| GET | `/public/tournaments/{tournamentId}/teams` | Get teams by tournament |

### Protected Endpoints (Requires Service Token)

#### Team Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tournaments/{tournamentId}/teams` | Get teams by tournament |
| GET | `/teams` | List all teams (with pagination and search) |
| POST | `/teams` | Create a new team |
| GET | `/teams/{id}` | Get team details |
| PUT | `/teams/{id}` | Update team |
| DELETE | `/teams/{id}` | Delete team |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by team name
- `tournament_id` (optional): Filter by tournament ID

**Request Body (Create/Update):**
```json
{
    "name": "Team Name",
    "short_name": "TNM",
    "logo_url": "https://example.com/logo.png",
    "founded_year": 2020,
    "country": "Country Name",
    "city": "City Name"
}
```

#### Player Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/players` | List all players (with pagination and search) |
| POST | `/players` | Create a new player |
| GET | `/players/{id}` | Get player details |
| PUT | `/players/{id}` | Update player |
| DELETE | `/players/{id}` | Delete player |
| GET | `/teams/{teamId}/players` | Get players by team |
| GET | `/teams/{teamId}/players/{playerId}/validate` | Validate player belongs to team |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by player name
- `team_id` (optional): Filter by team ID

**Request Body (Create/Update):**
```json
{
    "team_id": 1,
    "name": "Player Name",
    "position": "forward",
    "jersey_number": 10,
    "date_of_birth": "1995-01-15",
    "nationality": "Country",
    "height": 180,
    "weight": 75
}
```

**Player Positions:**
- `goalkeeper`
- `defender`
- `midfielder`
- `forward`

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Team created successfully",
    "data": {
        "id": 1,
        "name": "Team Name",
        "short_name": "TNM",
        "logo_url": "https://example.com/logo.png",
        "founded_year": 2020,
        "country": "Country Name",
        "city": "City Name",
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
    "message": "Teams retrieved successfully",
    "data": [
        // Array of teams
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

### Team Overview Response
```json
{
    "success": true,
    "message": "Team overview retrieved successfully",
    "data": {
        "id": 1,
        "name": "Team Name",
        "short_name": "TNM",
        "logo_url": "https://example.com/logo.png",
        "founded_year": 2020,
        "country": "Country Name",
        "city": "City Name",
        "squad_size": 25,
        "matches_played": 10,
        "matches_won": 6,
        "matches_drawn": 2,
        "matches_lost": 2,
        "total_goals": 20,
        "players": [
            // Array of players
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
cd team-service
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
DB_DATABASE=team_service
DB_USERNAME=root
DB_PASSWORD=
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Start the development server**
```bash
php artisan serve --port=8004
```

The service will be available at `http://localhost:8004`

## Environment Variables

Key environment variables to configure:

```env
APP_NAME="Team Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8004

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=team_service
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
RESULTS_SERVICE_URL=http://localhost:8005
```

## Database Schema

### Teams Table
- `id`: Primary key
- `name`: Team name
- `short_name`: Team short name/abbreviation
- `logo_url`: URL to team logo
- `founded_year`: Year team was founded
- `country`: Country name
- `city`: City name
- `created_at`, `updated_at`: Timestamps

### Players Table
- `id`: Primary key
- `team_id`: Foreign key to teams
- `name`: Player full name
- `position`: Player position
- `jersey_number`: Jersey number
- `date_of_birth`: Date of birth
- `nationality`: Player nationality
- `height`: Height in centimeters
- `weight`: Weight in kilograms
- `created_at`, `updated_at`: Timestamps

### Team Tournament Pivot Table
- `team_id`: Foreign key to teams
- `tournament_id`: Foreign key to tournaments
- `created_at`, `updated_at`: Timestamps

## Event Publishing

The service publishes events to a message queue for other services:

- `team.created`: When a new team is created
- `team.updated`: When a team is updated
- `team.deleted`: When a team is deleted
- `player.created`: When a new player is created
- `player.updated`: When a player is updated
- `player.deleted`: When a player is deleted
- `team.tournament.assigned`: When a team is assigned to a tournament
- `team.tournament.removed`: When a team is removed from a tournament

## Event Consumption

The service consumes events from other services:

- `tournament.*`: Tournament-related events
- `match.*`: Match-related events for statistics

## Usage Examples

### Create a Team
```bash
curl -X POST http://localhost:8004/api/v1/teams \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Team Name",
    "short_name": "TNM",
    "logo_url": "https://example.com/logo.png",
    "founded_year": 2020,
    "country": "Country Name",
    "city": "City Name"
  }'
```

### Get Team Profile (Public)
```bash
curl -X GET http://localhost:8004/api/v1/public/teams/1
```

### Create a Player
```bash
curl -X POST http://localhost:8004/api/v1/players \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "team_id": 1,
    "name": "Player Name",
    "position": "forward",
    "jersey_number": 10,
    "date_of_birth": "1995-01-15",
    "nationality": "Country",
    "height": 180,
    "weight": 75
  }'
```

### Get Team Squad
```bash
curl -X GET http://localhost:8004/api/v1/public/teams/1/squad
```

### Get Teams by Tournament
```bash
curl -X GET http://localhost:8004/api/v1/public/tournaments/1/teams
```

### List Teams with Search
```bash
curl -X GET "http://localhost:8004/api/v1/teams?per_page=10&search=team" \
  -H "Authorization: Bearer YOUR_SERVICE_TOKEN"
```

## Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `UNAUTHORIZED` | Authentication required |
| `TEAM_NOT_FOUND` | Team not found |
| `PLAYER_NOT_FOUND` | Player not found |
| `TOURNAMENT_NOT_FOUND` | Tournament not found |
| `DUPLICATE_JERSEY_NUMBER` | Jersey number already exists for team |
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
supervisorctl start team-service-queue:*
```

## Contributing

1. Follow the existing code structure
2. Write tests for new features
3. Update documentation
4. Follow PSR-12 coding standards

## License

This service is part of the Sports Tournament Architecture project.
