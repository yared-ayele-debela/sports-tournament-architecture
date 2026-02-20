# Distributed Sports Tournament Management System

A modern, microservices-based architecture for managing sports tournaments, matches, teams, and results. Built with Laravel and following microservices best practices.

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Services](#services)
- [Technology Stack](#technology-stack)
- [Features](#features)
- [Getting Started](#getting-started)
- [API Documentation](#api-documentation)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

## 🎯 Overview

This project implements a distributed microservices architecture for managing sports tournaments. The system is designed to handle tournament creation, team management, match scheduling, live match updates, results tracking, and comprehensive statistics.

### Key Design Principles

- **Microservices Architecture**: Each service is independently deployable and scalable
- **Event-Driven Communication**: Services communicate via Redis Pub/Sub for loose coupling
- **Database per Service**: Each service maintains its own database for data isolation
- **Direct Client Access**: Admin Dashboard and Public View access services directly (no API Gateway)
- **RESTful APIs**: Standardized JSON APIs with consistent response formats

## 🏗️ Architecture

### System Architecture

The system follows a microservices architecture with 5 core services. Clients (Admin Dashboard and Public View) access services directly via HTTP/REST APIs.

![System Architecture Diagram](docs/System%20Architecture%20Diagram.png)

**Key Components:**
- **5 Microservices**: Auth (8001), Tournament (8002), Team (8003), Match (8004), Results (8005)
- **5 Databases**: One dedicated database per service
- **Redis**: Event queue for asynchronous communication
- **Clients**: Admin Dashboard (protected APIs) and Public View (public APIs)

### Data Flow

The following diagram shows how data flows through the system:

![Data Flow Diagram](docs/Data%20Flow%20Diagram.png)

**Flow Example: Tournament Creation**
1. Admin Dashboard sends request to Tournament Service
2. Tournament Service validates token with Auth Service
3. Tournament Service saves to database
4. Tournament Service publishes event to Redis
5. Other services consume the event and update their caches

### Event Flow

Services communicate asynchronously via Redis Pub/Sub for event-driven updates:

![Event Flow Diagram](docs/Event%20Flow%20Diagram.png)

**Event Types:**
- `tournament.created`, `tournament.updated`, `tournament.status.changed`
- `match.completed`, `match.scheduled`
- `standings.updated`, `statistics.updated`
- `user.registered`, `user.logged.in`

### Communication Patterns

1. **Synchronous**: HTTP/REST for direct service-to-service calls and client requests
2. **Asynchronous**: Redis Pub/Sub for event-driven communication
3. **Real-time**: WebSocket (Pusher) for live match updates
4. **Authentication**: Token-based authentication via Auth Service (all services validate tokens)

### WebSocket Architecture

The system uses Pusher WebSocket service for real-time live match updates. Match Service broadcasts events to Pusher, which then delivers updates to all connected clients in real-time.

![WebSocket Architecture Diagram](docs/WebSocket%20Architecture%20Diagram.png)

**WebSocket Flow:**
1. Match Service creates/updates match events, status, scores, or minutes
2. Match Service broadcasts events to Pusher using Laravel Broadcasting
3. Pusher WebSocket server receives events and broadcasts to subscribed channels
4. Frontend clients (Public View, Admin Dashboard) connect via Laravel Echo and Pusher JS
5. Clients receive real-time updates and update UI automatically

**Channels:**
- `match.{matchId}` - Specific match updates
- `match.live` - All live matches
- `match.events` - All match events
- `match.scores` - Score updates

**Broadcast Events:**
- `MatchEventRecorded` - When goals, cards, or substitutions are recorded
- `MatchStatusChanged` - When match status changes (scheduled → in_progress → completed)
- `MatchScoreUpdated` - When match score changes
- `MatchMinuteUpdated` - When current minute of match updates

### Sequence Diagrams

For detailed flow diagrams, see the sequence diagrams:

- [Tournament Creation Flow](docs/Tournament%20Creation%20Flow-.png)
- [Match Completion Flow](docs/Match%20Completion%20Flow.png)
- [Match Status Change Flow](docs/Sequence%20Diagram%20-%20Match%20Status%20Change.png)
- [User Authentication Flow](docs/User%20Authentication%20Flow.png)

## 🚀 Services

### 1. Auth Service (Port 8001)

**Base URL:** `http://localhost:8001/api`

**Responsibilities:**
- User authentication and authorization
- Role-based access control (RBAC)
- Permission management
- Token generation and validation

**Implemented Features:**
- User registration and login (public endpoints)
- User logout, token refresh, and profile (protected endpoints)
- User CRUD operations (protected endpoints)
- Role CRUD operations (protected endpoints)
- Permission CRUD operations (protected endpoints)
- Service-to-service user validation endpoints
- Statistics endpoint
- Health check endpoint

**Public Endpoints:**
- `POST /auth/register` - Register new user
- `POST /auth/login` - User login
- `GET /health` - Health check
- `GET /statistics` - Service statistics

**Protected Endpoints:**
- `POST /auth/logout` - Logout user
- `POST /auth/refresh` - Refresh token
- `GET /auth/me` - Get authenticated user profile
- `GET|POST|PUT|PATCH|DELETE /admin/users` - User management
- `GET|POST|PUT|PATCH|DELETE /admin/roles` - Role management
- `GET|POST|PUT|PATCH|DELETE /admin/permissions` - Permission management
- `GET /users/{id}` - Get user (internal service endpoint)
- `GET /users/{id}/validate` - Validate user (internal service endpoint)
- `POST /users/{id}/roles` - Assign role to user
- `GET /users/{id}/permissions` - Get user permissions
- `POST /users/validate` - Validate user by email

**Documentation:** [auth-service/README.md](./auth-service/README.md)

### 2. Tournament Service (Port 8002)

**Base URL:** `http://localhost:8002/api`

**Responsibilities:**
- Tournament lifecycle management
- Sports management
- Venue management
- Tournament settings configuration

**Implemented Features:**
- Tournament CRUD operations
- Tournament status management
- Tournament overview, statistics, and standings
- Sports CRUD operations
- Venue CRUD operations
- Tournament settings management
- Public tournament information endpoints

**Public Endpoints:**
- `GET /tournaments` - List all tournaments
- `GET /tournaments/{id}` - Get tournament details
- `GET /tournaments/{id}/matches` - Get tournament matches
- `GET /tournaments/{id}/teams` - Get tournament teams
- `GET /tournaments/{id}/overview` - Get tournament overview
- `GET /tournaments/{id}/statistics` - Get tournament statistics
- `GET /tournaments/{id}/standings` - Get tournament standings
- `GET /statistics` - Service statistics
- `GET /health` - Health check

**Protected Endpoints:**
- `POST|PUT|DELETE /tournaments` - Tournament management
- `PATCH /tournaments/{id}/status` - Update tournament status
- `GET|POST /tournaments/{id}/settings` - Tournament settings
- `GET|POST|PUT|DELETE /sports` - Sports management
- `GET|POST|PUT|DELETE /venues` - Venues management

**Documentation:** [tournament-service/README.md](./tournament-service/README.md)

### 3. Team Service (Port 8003)

**Base URL:** `http://localhost:8003/api`

**Responsibilities:**
- Team management
- Player management
- Squad management
- Team statistics

**Implemented Features:**
- Team CRUD operations
- Player CRUD operations
- Team-tournament associations
- Public team profiles and squad information
- Team statistics and match history

**Public Endpoints:**
- `GET /public/teams/{id}` - Get team profile
- `GET /public/teams/{id}/overview` - Get team overview
- `GET /public/teams/{id}/squad` - Get team squad/players
- `GET /public/teams/{id}/matches` - Get team matches
- `GET /public/teams/{id}/statistics` - Get team statistics
- `GET /public/tournaments/{tournamentId}/teams` - Get teams by tournament
- `GET /statistics` - Service statistics
- `GET /health` - Health check

**Protected Endpoints:**
- `GET|POST|PUT|DELETE /teams` - Team management
- `GET /tournaments/{tournamentId}/teams` - Get teams by tournament
- `GET /teams/{id}/players` - Get players by team
- `GET /teams/{teamId}/players/{playerId}/validate` - Validate player belongs to team
- `GET|POST|PUT|DELETE /players` - Player management

**Documentation:** [team-service/README.md](./team-service/README.md)

### 4. Match Service (Port 8004)

**Base URL:** `http://localhost:8004/api`

**Responsibilities:**
- Match creation and scheduling
- Match status management
- Match events tracking (goals, cards, substitutions)
- Match reports

**Implemented Features:**
- Match CRUD operations
- Automatic schedule generation
- Live match updates and status management
- Match event tracking (goals, cards, substitutions)
- Match report generation
- Public match information endpoints

**Public Endpoints:**
- `GET /public/tournaments/{tournamentId}/matches` - Get tournament matches
- `GET /public/matches` - List all matches
- `GET /public/matches/live` - Get live matches
- `GET /public/matches/upcoming` - Get upcoming matches
- `GET /public/matches/completed` - Get completed matches
- `GET /public/matches/date/{date}` - Get matches by date
- `GET /public/matches/{id}` - Get match details
- `GET /public/matches/{id}/events/public` - Get match events
- `GET /statistics` - Service statistics
- `GET /statistics/matches-by-status` - Match statistics by status
- `GET /health` - Health check

**Protected Endpoints:**
- `GET|POST|PUT|DELETE /matches` - Match management
- `PATCH /matches/{id}/status` - Update match status
- `POST /tournaments/{tournamentId}/generate-schedule` - Generate match schedule
- `GET|POST|PUT|DELETE /matches/{matchId}/events` - Match events management
- `GET|POST /matches/{matchId}/report` - Match reports
- `GET /statistics/coach/matches-by-status` - Coach match statistics
- `GET /statistics/referee/matches-by-status` - Referee match statistics

**Documentation:** [match-service/README.md](./match-service/README.md)

### 5. Results Service (Port 8005)

**Base URL:** `http://localhost:8005/api`

**Responsibilities:**
- Tournament standings calculation
- Match results finalization
- Statistics aggregation
- Top scorers tracking

**Implemented Features:**
- Automatic standings calculation
- Match results finalization
- Tournament and team statistics
- Public standings and statistics endpoints

**Public Endpoints:**
- `GET /tournaments/{tournamentId}/standings` - Get tournament standings
- `GET /tournaments/{tournamentId}/statistics` - Get tournament statistics
- `GET /statistics` - Service statistics
- `GET /statistics/goals-per-tournament` - Goals per tournament statistics
- `GET /statistics/top-scoring-teams` - Top scoring teams statistics
- `GET /health` - Health check

**Protected Endpoints:**
- `POST /standings/recalculate/{tournamentId}` - Manually recalculate standings
- `GET /tournaments/{tournamentId}/results` - Get tournament results
- `GET /results/{id}` - Get specific match result
- `POST /matches/{matchId}/finalize` - Finalize match result
- `GET /teams/{teamId}/statistics` - Get team statistics

**Documentation:** [results-service/README.md](./results-service/README.md)

## 💻 Technology Stack

### Backend
- **Framework**: Laravel 11/12
- **Language**: PHP 8.2+
- **Authentication**: Laravel Passport (OAuth2)
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Event System**: Redis Pub/Sub

### Frontend
- **Admin Dashboard**: React + Vite
- **Public App**: React + Vite
- **Styling**: Tailwind CSS

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Database Management**: phpMyAdmin
- **Version Control**: Git

## ✨ Features

### Core Features
- ✅ User authentication and authorization (Auth Service)
- ✅ User, Role, and Permission management (Auth Service)
- ✅ Tournament CRUD operations (Tournament Service)
- ✅ Sports and Venues management (Tournament Service)
- ✅ Team and Player CRUD operations (Team Service)
- ✅ Match CRUD operations and scheduling (Match Service)
- ✅ Match events tracking (goals, cards, substitutions) (Match Service)
- ✅ Match reports (Match Service)
- ✅ Tournament standings calculation (Results Service)
- ✅ Match results finalization (Results Service)
- ✅ Statistics aggregation (All Services)
- ✅ Public and protected APIs (All Services)
- ✅ Health check endpoints (All Services)

### Advanced Features
- ✅ Event-driven architecture (Redis Pub/Sub)
- ✅ Real-time cache invalidation
- ✅ Service-to-service communication
- ✅ Public and protected API endpoints
- ✅ Rate limiting (where implemented)
- ✅ Caching strategies
- ✅ Health monitoring endpoints

## 🚀 Getting Started

### Prerequisites

- **Docker** 20.10+
- **Docker Compose** 2.0+
- **Git**
- **PHP 8.2+** (for local development)
- **Composer** (for local development)
- **Node.js 18+** (for frontend development)

> **📖 For detailed Docker setup instructions, see [DOCKER_SETUP_GUIDE.md](./DOCKER_SETUP_GUIDE.md)**

### Quick Start with Docker

1. **Clone the repository**
```bash
git clone <repository-url>
cd sports-tournament-architecture/distributed
```

2. **Start all services**
```bash
docker-compose up -d
```

This will start all services, databases, and Redis in detached mode.

3. **Set up services using setup scripts**

The easiest way to set up each service is using the provided setup scripts:

```bash
# Set up Auth Service (includes migrations, Passport installation, seeding)
./setup-auth-service.sh

# Set up Tournament Service
./setup-tournament-service.sh

# Set up Team Service
./setup-team-service.sh

# Set up Match Service
./setup-match-service.sh

# Set up Results Service
./setup-results-service.sh
```

**Or set up all services at once:**
```bash
./seed-all.sh
```

4. **Access the services**
- **Auth Service**: http://localhost:8001
- **Tournament Service**: http://localhost:8002
- **Team Service**: http://localhost:8003
- **Match Service**: http://localhost:8004
- **Results Service**: http://localhost:8005
- **phpMyAdmin**: http://localhost:8080

### Docker Compose Commands

#### Starting Services

```bash
# Start all services in detached mode (background)
docker-compose up -d

# Start specific service
docker-compose up -d auth-service

# Start services and rebuild images
docker-compose up -d --build

# Start services and recreate containers
docker-compose up -d --force-recreate
```

#### Stopping Services

```bash
# Stop all services
docker-compose stop

# Stop specific service
docker-compose stop auth-service

# Stop and remove containers
docker-compose down

# Stop and remove containers, volumes, and networks
docker-compose down -v
```

#### Viewing Logs

```bash
# View logs for all services
docker-compose logs

# View logs for specific service
docker-compose logs auth-service

# Follow logs (real-time)
docker-compose logs -f auth-service

# View last 100 lines
docker-compose logs --tail=100 auth-service
```

#### Checking Status

```bash
# Check running services
docker-compose ps

# Check service health
curl http://localhost:8001/api/health
curl http://localhost:8002/api/health
curl http://localhost:8003/api/health
curl http://localhost:8004/api/health
curl http://localhost:8005/api/health
```

#### Rebuilding Services

```bash
# Rebuild specific service
docker-compose build auth-service

# Rebuild all services
docker-compose build

# Rebuild and restart
docker-compose up -d --build auth-service
```

#### Executing Commands in Containers

```bash
# Run artisan commands
docker-compose exec auth-service php artisan migrate
docker-compose exec auth-service php artisan cache:clear

# Access container shell
docker-compose exec auth-service bash

# Run composer commands
docker-compose exec auth-service composer install

# Check service environment variables
docker-compose exec auth-service printenv
```

#### Restarting Services

```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart auth-service
```

#### Database Access

```bash
# Access MySQL via command line
docker-compose exec auth-db mysql -uroot -prootpassword

# Access via phpMyAdmin
# Open http://localhost:8080 in browser
# Login with: root / rootpassword
```

#### Redis Access

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Check Redis keys
docker-compose exec redis redis-cli KEYS "*"

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHDB
```

#### Cleanup Commands

```bash
# Remove stopped containers
docker-compose rm

# Remove containers and volumes
docker-compose down -v

# Remove unused images
docker image prune

# Full cleanup (containers, volumes, networks, images)
docker-compose down -v --rmi all
```

### Manual Setup (Alternative to Setup Scripts)

If you prefer to set up services manually:

1. **Run migrations**
```bash
docker-compose exec auth-service php artisan migrate --force
docker-compose exec tournament-service php artisan migrate --force
docker-compose exec team-service php artisan migrate --force
docker-compose exec match-service php artisan migrate --force
docker-compose exec results-service php artisan migrate --force
```

2. **Install Passport (Auth Service only)**
```bash
docker-compose exec auth-service php artisan passport:install --force
docker-compose exec auth-service php artisan passport:keys --force
docker-compose exec auth-service php artisan passport:client --personal --name="Personal Access Client" --no-interaction
```

3. **Seed databases**
```bash
docker-compose exec auth-service php artisan db:seed --force
docker-compose exec tournament-service php artisan db:seed --force
docker-compose exec team-service php artisan db:seed --force
docker-compose exec match-service php artisan db:seed --force
docker-compose exec results-service php artisan db:seed --force
```

### Local Development Setup

1. **Set up each service individually**

```bash
# Example for auth-service
cd auth-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:install
php artisan serve --port=8001
```

2. **Set up frontend applications**

```bash
# Admin Dashboard
cd admin-dashboard
npm install
npm run dev

# Public View
cd public-view
npm install
npm run dev
```

3. **Configure service URLs**

Update `.env` files in each service with correct service URLs:
```env
AUTH_SERVICE_URL=http://localhost:8001
TOURNAMENT_SERVICE_URL=http://localhost:8002
TEAM_SERVICE_URL=http://localhost:8003
MATCH_SERVICE_URL=http://localhost:8004
RESULTS_SERVICE_URL=http://localhost:8005
```

### Environment Variables

Each service requires specific environment variables. See individual service README files for details:

- [Auth Service Environment Variables](./auth-service/README.md#environment-variables)
- [Tournament Service Environment Variables](./tournament-service/README.md#environment-variables)
- [Team Service Environment Variables](./team-service/README.md#environment-variables)
- [Match Service Environment Variables](./match-service/README.md#environment-variables)
- [Results Service Environment Variables](./results-service/README.md#environment-variables)

## 📚 API Documentation

### Base URLs

- **Auth Service**: `http://localhost:8001/api`
- **Tournament Service**: `http://localhost:8002/api`
- **Team Service**: `http://localhost:8003/api`
- **Match Service**: `http://localhost:8004/api`
- **Results Service**: `http://localhost:8005/api`

**Note**: Each service provides both public and protected API endpoints. Public endpoints are accessible without authentication, while protected endpoints require Bearer token authentication.

### OpenAPI Specifications

All services have comprehensive OpenAPI 3.0.3 specification files:

- **Auth Service**: [`auth-service/openapi.yaml`](./auth-service/openapi.yaml)
- **Tournament Service**: [`tournament-service/openapi.yaml`](./tournament-service/openapi.yaml)
- **Team Service**: [`team-service/openapi.yaml`](./team-service/openapi.yaml)
- **Match Service**: [`match-service/openapi.yaml`](./match-service/openapi.yaml)
- **Results Service**: [`results-service/openapi.yaml`](./results-service/openapi.yaml)

### Viewing OpenAPI Documentation

To view and interact with the OpenAPI specifications, see the [OpenAPI Viewer Guide](./VIEW_OPENAPI_GUIDE.md) for multiple options:

- **Online Tools**: Use Swagger Editor (https://editor.swagger.io/) or Redoc (https://redocly.github.io/redoc/)
- **Docker**: Run Swagger UI containers for all services
- **Laravel Integration**: Integrate Swagger UI directly into Laravel services

**Quick Start**: Copy any `openapi.yaml` file content and paste it into [Swagger Editor](https://editor.swagger.io/) for instant viewing.

### Service-Specific API Documentation

Each service also provides detailed API documentation in their README files:

- [Auth Service API](./auth-service/README.md#api-endpoints)
- [Tournament Service API](./tournament-service/README.md#api-endpoints)
- [Team Service API](./team-service/README.md#api-endpoints)
- [Match Service API](./match-service/README.md#api-endpoints)
- [Results Service API](./results-service/README.md#api-endpoints)

### Health Check Endpoints

All services provide health check endpoints:
- Auth Service: `GET http://localhost:8001/api/health`
- Tournament Service: `GET http://localhost:8002/api/health`
- Team Service: `GET http://localhost:8003/api/health`
- Match Service: `GET http://localhost:8004/api/health`
- Results Service: `GET http://localhost:8005/api/health`

### Authentication

Most endpoints require authentication using Bearer tokens:

```bash
curl -X GET http://localhost:8002/api/tournaments \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

Get an access token by logging in:
```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

## 🛠️ Development

### Project Structure

```
distributed/
├── auth-service/          # Authentication service (Port 8001)
│   └── openapi.yaml       # OpenAPI 3.0.3 specification
├── tournament-service/     # Tournament management (Port 8002)
│   └── openapi.yaml       # OpenAPI 3.0.3 specification
├── team-service/          # Team and player management (Port 8003)
│   └── openapi.yaml       # OpenAPI 3.0.3 specification
├── match-service/         # Match management (Port 8004)
│   └── openapi.yaml       # OpenAPI 3.0.3 specification
├── results-service/       # Results and statistics (Port 8005)
│   └── openapi.yaml       # OpenAPI 3.0.3 specification
├── admin-dashboard/       # Admin frontend
├── public-view/           # Public frontend
├── docs/                  # Architecture diagrams and documentation
│   ├── System Architecture Diagram.png
│   ├── Data Flow Diagram.png
│   ├── Event Flow Diagram.png
│   ├── WebSocket Architecture Diagram.png
│   ├── Tournament Creation Flow-.png
│   ├── Match Completion Flow.png
│   ├── Sequence Diagram - Match Status Change.png
│   └── User Authentication Flow.png
├── docker-compose.yml      # Docker orchestration
├── VIEW_OPENAPI_GUIDE.md  # Guide for viewing OpenAPI specs
├── DOCKER_SETUP_GUIDE.md  # Docker setup instructions
└── README.md              # This file
```

### Development Workflow

1. **Service Development**
   - Each service can be developed independently
   - Use feature branches for new features
   - Follow PSR-12 coding standards

2. **Event Development**
   - Define event structure
   - Update event publishers
   - Update event consumers
   - Test event flow

3. **API Development**
   - Follow RESTful conventions
   - Use consistent response format
   - Add proper validation
   - Document endpoints

### Code Standards

- **PHP**: PSR-12 coding standard
- **JavaScript**: ESLint configuration
- **Git**: Conventional commits
- **Documentation**: Inline code comments and README files

### Running Services Locally

```bash
# Start Redis (required for events and cache)
docker run -d -p 6379:6379 redis:alpine

# Start each service
cd auth-service && php artisan serve --port=8001
cd tournament-service && php artisan serve --port=8002
cd team-service && php artisan serve --port=8003
cd match-service && php artisan serve --port=8004
cd results-service && php artisan serve --port=8005
```

## 🧪 Testing

### Test Coverage

- Unit tests for business logic
- Integration tests for API endpoints
- Event flow tests
- Service-to-service communication tests

### Test Data

Use seeders to populate test data:
```bash
php artisan db:seed
```

## 🚢 Deployment

> **📖 For comprehensive Docker setup and deployment instructions, see [DOCKER_SETUP_GUIDE.md](./DOCKER_SETUP_GUIDE.md)**

### Docker Deployment

#### Initial Setup

1. **Build and start all services**
```bash
docker-compose up -d --build
```

2. **Wait for services to be ready**
```bash
# Check service status
docker-compose ps


3. **Set up services using setup scripts (Recommended)**
```bash
# Set up each service (includes migrations, Passport setup, seeding)
./setup-auth-service.sh
./setup-tournament-service.sh
./setup-team-service.sh
./setup-match-service.sh
./setup-results-service.sh

```

#### Manual Setup (Alternative)

If you prefer manual setup:

1. **Run migrations for all services**
```bash
docker-compose exec auth-service php artisan migrate --force
docker-compose exec tournament-service php artisan migrate --force
docker-compose exec team-service php artisan migrate --force
docker-compose exec match-service php artisan migrate --force
docker-compose exec results-service php artisan migrate --force
```

2. **Set up Passport (Auth Service only)**
```bash
docker-compose exec auth-service php artisan passport:install --force
docker-compose exec auth-service php artisan passport:keys --force
docker-compose exec auth-service php artisan passport:client --personal --name="Personal Access Client" --no-interaction
```

3. **Seed databases**
```bash
docker-compose exec auth-service php artisan db:seed --force
docker-compose exec tournament-service php artisan db:seed --force
docker-compose exec team-service php artisan db:seed --force
docker-compose exec match-service php artisan db:seed --force
docker-compose exec results-service php artisan db:seed --force
```

#### Updating Services

When you make code changes:

1. **Rebuild and restart specific service**
```bash
docker-compose build match-service
docker-compose up -d --force-recreate match-service
```

2. **Clear cache after updates**
```bash
docker-compose exec match-service php artisan cache:clear
docker-compose exec match-service php artisan config:clear
docker-compose exec match-service php artisan route:clear
docker-compose exec match-service php artisan view:clear
```

3. **Rebuild all services**
```bash
docker-compose build
docker-compose up -d
```

### Production Considerations

- Use environment variables for all secrets
- Set up proper database backups
- Configure Redis for high availability
- Implement health checks and monitoring
- Set up log aggregation
- Configure rate limiting
- Enable HTTPS/TLS
- Set up CI/CD pipeline

### Health Checks

All services provide health check endpoints:
```bash
curl http://localhost:8001/api/health
curl http://localhost:8002/api/health
curl http://localhost:8003/api/health
curl http://localhost:8004/api/health
curl http://localhost:8005/api/health
```

## 🔧 Troubleshooting

### Common Issues

#### Services can't connect to databases
- Check database containers are running: `docker-compose ps`
- Verify database credentials in `.env` files
- Check network connectivity: `docker-compose exec auth-service ping auth-db`

#### Redis connection errors
- Ensure Redis container is running
- Check Redis configuration in `.env` files
- Verify Redis port (default: 6379)

#### Service-to-service authentication fails
- Verify Auth Service is running
- Check service URLs in configuration
- Ensure tokens are being passed correctly

#### Events not being consumed
- Check Redis Pub/Sub connection
- Verify event channels are correct
- Check service logs for errors
- Ensure queue workers are running

### Debugging

1. **Check service logs**
```bash
docker-compose logs auth-service
docker-compose logs -f auth-service  # Follow logs
```

2. **Access service containers**
```bash
docker-compose exec auth-service bash
```

3. **Check Redis**
```bash
docker-compose exec redis redis-cli
> PING
> PUBSUB CHANNELS
```

4. **Database access**
```bash
# Via phpMyAdmin: http://localhost:8080
# Or via command line
docker-compose exec auth-db mysql -u root -p
```

### Getting Help

- Check individual service README files
- Review [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) (if exists)
- Check service-specific troubleshooting docs
- Review logs for error messages

## Error Handling

All services use standardized error responses with error codes. See [ERROR_CODES.md](./ERROR_CODES.md) for the complete reference.

### Standard Error Response Format

```json
{
    "success": false,
    "message": "Error description",
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
| `VALIDATION_ERROR` | 422 | Validation failed |
| `INTERNAL_SERVER_ERROR` | 500 | Server error |

For detailed error code documentation, see [ERROR_CODES.md](./ERROR_CODES.md).

## 📖 Additional Documentation

### Setup and Configuration Guides

- [Docker Setup Guide](./DOCKER_SETUP_GUIDE.md) - Comprehensive Docker setup and deployment instructions
- [OpenAPI Viewer Guide](./VIEW_OPENAPI_GUIDE.md) - How to view and interact with OpenAPI specifications


### Architecture Diagrams

All architecture diagrams are located in the [`docs/`](./docs/) directory:

- **System Architecture Diagram** - Overall system architecture
- **Data Flow Diagram** - How data flows through the system
- **Event Flow Diagram** - Event-driven communication patterns
- **WebSocket Architecture Diagram** - Real-time WebSocket communication using Pusher for live match updates
- **Sequence Diagrams** - Detailed flow diagrams for key operations:
  - Tournament Creation Flow
  - Match Completion Flow
  - Match Status Change Flow - Real-time status updates via WebSocket
  - User Authentication Flow

### Service Documentation

Each service has its own detailed README:

- [Auth Service](./auth-service/README.md)
- [Tournament Service](./tournament-service/README.md)
- [Team Service](./team-service/README.md)
- [Match Service](./match-service/README.md)
- [Results Service](./results-service/README.md)

## 📝 License

This project is part of a Software Architecture course project.


## 📞 Support

For issues and questions:
- Check the documentation
- Review service-specific README files
- Check troubleshooting section
- Review logs for errors

---

