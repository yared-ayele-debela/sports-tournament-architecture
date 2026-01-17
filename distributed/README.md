# Distributed Soccer Tournament System

A microservices-based architecture for managing soccer tournaments.

## Services

- **auth-service** (Port 8001) - Authentication and authorization
- **tournament-service** (Port 8002) - Tournament management
- **team-service** (Port 8003) - Team and player management
- **match-service** (Port 8004) - Match scheduling and management
- **results-service** (Port 8005) - Match results and statistics
- **gateway-service** (Port 8000) - API Gateway and load balancer

## Architecture

```
├── auth-service/           (Port 8001)
├── tournament-service/     (Port 8002)
├── team-service/          (Port 8003)
├── match-service/         (Port 8004)
├── results-service/       (Port 8005)
├── gateway-service/       (Port 8000)
├── docker-compose.yml
└── README.md
```

## Getting Started

1. Ensure Docker and Docker Compose are installed
2. Run `docker-compose up` to start all services
3. API Gateway will be available at http://localhost:8000

## Development

Each service is independently deployable and can be developed in isolation.
