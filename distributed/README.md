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
- **API Gateway Pattern**: Single entry point for all client requests
- **RESTful APIs**: Standardized JSON APIs with consistent response formats

## 🏗️ Architecture

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Clients                              │
│              (Web Apps, Mobile Apps, Admin Dashboard)       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    API Gateway                              │
│              (Port 8000) - Request Routing                 │
│              Rate Limiting, Caching, Aggregation          │
└─────┬──────┬──────┬──────┬──────┬──────┬───────────────────┘
      │      │      │      │      │      │
      ▼      ▼      ▼      ▼      ▼      ▼
┌────────┐ ┌──────────┐ ┌──────┐ ┌──────┐ ┌────────┐ ┌──────────┐
│  Auth  │ │Tournament│ │ Team │ │Match │ │Results │ │  Redis   │
│Service │ │ Service  │ │Service│ │Service│ Service│ │Pub/Sub   │
│ :8001  │ │  :8002   │ │ :8003│ │ :8004│ │ :8005  │ │  Events  │
└───┬────┘ └────┬─────┘ └───┬──┘ └───┬──┘ └───┬────┘ └──────────┘
    │          │            │        │        │
    ▼          ▼            ▼        ▼        ▼
┌────────┐ ┌──────────┐ ┌──────┐ ┌──────┐ ┌────────┐
│ Auth   │ │Tournament│ │ Team │ │Match │ │Results │
│  DB    │ │   DB     │ │  DB  │ │  DB  │ │  DB    │
└────────┘ └──────────┘ └──────┘ └──────┘ └────────┘
```

### Communication Patterns

1. **Synchronous**: HTTP/REST for direct service-to-service calls
2. **Asynchronous**: Redis Pub/Sub for event-driven communication
3. **Authentication**: Token-based authentication via Auth Service

### Data Flow Example: Match Completion

```
1. Match Service completes match
   ↓
2. Publishes 'match.completed' event to Redis
   ↓
3. Results Service consumes event
   ↓
4. Results Service updates standings
   ↓
5. Results Service publishes 'standings.updated' event
   ↓
6. Gateway Service invalidates cache
```

## 🚀 Services

### 1. Auth Service (Port 8001)

**Responsibilities:**
- User authentication and authorization
- Role-based access control (RBAC)
- Permission management
- Token generation and validation

**Key Features:**
- Laravel Passport OAuth2
- User, Role, Permission CRUD
- JWT token management
- Service-to-service token validation

**Documentation:** [auth-service/README.md](./auth-service/README.md)

### 2. Tournament Service (Port 8002)

**Responsibilities:**
- Tournament lifecycle management
- Sports management
- Venue management
- Tournament settings configuration

**Key Features:**
- Tournament CRUD operations
- Status management (draft, published, ongoing, completed)
- Tournament formats (league, knockout, group stage)
- Public tournament information API

**Documentation:** [tournament-service/README.md](./tournament-service/README.md)

### 3. Team Service (Port 8003)

**Responsibilities:**
- Team management
- Player management
- Squad management
- Team statistics

**Key Features:**
- Team and player CRUD
- Team-tournament associations
- Player position management
- Public team profiles

**Documentation:** [team-service/README.md](./team-service/README.md)

### 4. Match Service (Port 8004)

**Responsibilities:**
- Match creation and scheduling
- Match status management
- Match events tracking (goals, cards, substitutions)
- Match reports

**Key Features:**
- Automatic schedule generation
- Live match updates
- Match event tracking
- Match report generation

**Documentation:** [match-service/README.md](./match-service/README.md)

### 5. Results Service (Port 8005)

**Responsibilities:**
- Tournament standings calculation
- Match results finalization
- Statistics aggregation
- Top scorers tracking

**Key Features:**
- Automatic standings recalculation
- Real-time statistics
- Team and player statistics
- Tournament statistics

**Documentation:** [results-service/README.md](./results-service/README.md)

### 6. Gateway Service (Port 8000)

**Responsibilities:**
- API routing and aggregation
- Request/response transformation
- Rate limiting
- Caching
- Search aggregation

**Key Features:**
- Public API aggregation
- Data composition from multiple services
- Global search functionality
- Health check aggregation

**Documentation:** [gateway-service/README.md](./gateway-service/README.md)

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
- ✅ User authentication and authorization
- ✅ Tournament management (create, update, delete)
- ✅ Team and player management
- ✅ Match scheduling and management
- ✅ Live match updates
- ✅ Results and standings tracking
- ✅ Comprehensive statistics
- ✅ Search functionality
- ✅ Public and protected APIs

### Advanced Features
- ✅ Event-driven architecture
- ✅ Real-time cache invalidation
- ✅ Service-to-service communication
- ✅ API Gateway with aggregation
- ✅ Rate limiting
- ✅ Caching strategies
- ✅ Health monitoring
- ✅ Self-documenting APIs

## 🚀 Getting Started

### Prerequisites

- **Docker** 20.10+
- **Docker Compose** 2.0+
- **Git**
- **PHP 8.2+** (for local development)
- **Composer** (for local development)
- **Node.js 18+** (for frontend development)

### Quick Start with Docker

1. **Clone the repository**
```bash
git clone <repository-url>
cd sports-tournament-architecture/distributed
```

2. **Set up environment variables**
```bash
# Copy environment files (if they exist)
# Each service should have its own .env file
```

3. **Start all services**
```bash
docker-compose up -d
```

4. **Run migrations**
```bash
# For each service, run migrations
docker-compose exec auth-service php artisan migrate
docker-compose exec tournament-service php artisan migrate
docker-compose exec team-service php artisan migrate
docker-compose exec match-service php artisan migrate
docker-compose exec results-service php artisan migrate
```

5. **Install Passport (Auth Service)**
```bash
docker-compose exec auth-service php artisan passport:install
```

6. **Access the services**
- **API Gateway**: http://localhost:8000
- **Auth Service**: http://localhost:8001
- **Tournament Service**: http://localhost:8002
- **Team Service**: http://localhost:8003
- **Match Service**: http://localhost:8004
- **Results Service**: http://localhost:8005
- **phpMyAdmin**: http://localhost:8080

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

# Public App
cd tournament-public-app
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
- [Gateway Service Environment Variables](./gateway-service/README.md#environment-variables)

## 📚 API Documentation

### Base URLs

- **API Gateway**: `http://localhost:8000/api/public`
- **Auth Service**: `http://localhost:8001/api/v1`
- **Tournament Service**: `http://localhost:8002/api`
- **Team Service**: `http://localhost:8003/api/v1`
- **Match Service**: `http://localhost:8004/api/v1`
- **Results Service**: `http://localhost:8005/api/v1`

### API Documentation

Each service provides comprehensive API documentation:

- [Auth Service API](./auth-service/README.md#api-endpoints)
- [Tournament Service API](./tournament-service/README.md#api-endpoints)
- [Team Service API](./team-service/README.md#api-endpoints)
- [Match Service API](./match-service/README.md#api-endpoints)
- [Results Service API](./results-service/README.md#api-endpoints)
- [Gateway Service API](./gateway-service/README.md#api-endpoints)

### Self-Documenting APIs

Some services provide auto-generated API documentation:
- Tournament Service: `GET /api/public/docs`
- Team Service: `GET /api/public/docs`
- Match Service: `GET /api/public/docs`
- Results Service: `GET /api/public/docs`

### Postman Collections

- [Auth Service Postman Collection](./auth-service/Auth-Service-CRUD.postman_collection.json)

### Authentication

Most endpoints require authentication using Bearer tokens:

```bash
curl -X GET http://localhost:8002/api/tournaments \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

Get an access token by logging in:
```bash
curl -X POST http://localhost:8001/api/v1/auth/login \
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
├── auth-service/          # Authentication service
├── tournament-service/     # Tournament management
├── team-service/          # Team and player management
├── match-service/         # Match management
├── results-service/       # Results and statistics
├── gateway-service/       # API Gateway
├── admin-dashboard/       # Admin frontend
├── tournament-public-app/  # Public frontend
├── docker-compose.yml      # Docker orchestration
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
cd gateway-service && php artisan serve --port=8000
```

## 🧪 Testing

### Running Tests

```bash
# Run tests for a specific service
cd auth-service
php artisan test

# Run all tests
find . -name "phpunit.xml" -execdir php artisan test \;
```

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

### Docker Deployment

1. **Build and start services**
```bash
docker-compose up -d --build
```

2. **Run migrations**
```bash
docker-compose exec auth-service php artisan migrate --force
# Repeat for other services
```

3. **Set up Passport**
```bash
docker-compose exec auth-service php artisan passport:install --force
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

Check service health:
```bash
curl http://localhost:8001/api/v1/health
curl http://localhost:8002/api/health
curl http://localhost:8003/api/v1/health
curl http://localhost:8004/api/v1/health
curl http://localhost:8005/api/v1/health
curl http://localhost:8000/api/health
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

## 📖 Additional Documentation

- [API Documentation](./API_DOCUMENTATION.md) - Self-documenting API endpoints
- [Search Implementation](./SEARCH_IMPLEMENTATION.md) - Search functionality details
- [Project Feedback](./PROJECT_FEEDBACK.md) - Improvement recommendations
- [Quick Improvements](./QUICK_IMPROVEMENTS.md) - Quick action checklist

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Contribution Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation
- Ensure all tests pass
- Follow the existing code structure

## 📝 License

This project is part of a Software Architecture course project.

## 🙏 Acknowledgments

- Laravel Framework
- Redis
- Docker
- All open-source contributors

## 📞 Support

For issues and questions:
- Check the documentation
- Review service-specific README files
- Check troubleshooting section
- Review logs for errors

---

**Built with ❤️ for Software Architecture Course**
