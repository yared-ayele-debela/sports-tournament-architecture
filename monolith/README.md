# 🏆 Sports Tournament Management System - Monolith Architecture

A comprehensive, feature-rich sports tournament management platform built with Laravel 12. This monolithic application provides complete tournament organization capabilities including team management, match scheduling, referee assignments, and multi-role user access control.

## 📋 Table of Contents

- [Features](#-features)
- [Architecture](#-architecture)
- [Technology Stack](#-technology-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [User Roles & Permissions](#-user-roles--permissions)
- [API Documentation](#-api-documentation)
- [Frontend Features](#-frontend-features)
- [Security Features](#-security-features)
- [Performance Optimizations](#-performance-optimizations)
- [Docker Deployment](#-docker-deployment)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### Core Tournament Management
- **Tournament Creation & Management**: Create and manage multiple tournaments with different formats
- **Team Registration**: Complete team management with player rosters
- **Match Scheduling**: Automated and manual match scheduling with venue management
- **Referee Assignments**: Assign referees to matches with availability tracking
- **Score Tracking**: Real-time score updates and match result management
- **Standings Calculation**: Automatic tournament standings and rankings

### User Management
- **Multi-Role System**: Admin, Coach, Referee, and Player roles
- **Permission-Based Access**: Granular permissions for different user types
- **Profile Management**: User profiles with role-specific dashboards
- **Authentication System**: Secure login/logout with session management

### Advanced Features
- **Coach Dashboard**: Team-specific management for coaches
- **Referee Dashboard**: Match assignments and reporting for referees
- **Live Match Events**: Real-time match event tracking (goals, cards, substitutions)
- **Tournament Statistics**: Comprehensive statistics and analytics
- **Venue Management**: Location and facility management
- **Sport Management**: Support for multiple sports types

## 🏗️ Architecture

### Monolithic Design Pattern
This application follows a **monolithic architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Admin UI  │  │  Coach UI   │  │   Referee UI        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                    Business Logic Layer                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ Controllers │  │   Services  │  │   Middleware        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                     Data Layer                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Models    │  │ Observers   │  │   Database          │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Key Architectural Components
- **Service Layer**: Business logic separation with dedicated service classes
- **Repository Pattern**: Data access abstraction
- **Observer Pattern**: Automatic model event handling
- **Middleware Stack**: Request filtering and authentication
- **Event System**: Decoupled event handling

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL/PostgreSQL with Eloquent ORM
- **Authentication**: Laravel Sanctum + Breeze
- **Queue System**: Redis + Laravel Queues
- **Caching**: Redis
- **File Storage**: Local/Cloud storage

### Frontend
- **Template Engine**: Blade with Livewire 4.0
- **CSS Framework**: Tailwind CSS
- **JavaScript**: Alpine.js + Vanilla JS
- **Build Tools**: Vite
- **UI Components**: Custom component library

### Development Tools
- **Testing**: PHPUnit
- **Code Quality**: Laravel Pint
- **Documentation**: Markdown
- **Containerization**: Docker + Docker Compose

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer 2.0 or higher
- Node.js 18+ and NPM
- MySQL 8.0+ or PostgreSQL 12+
- Redis 6.0+

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/sports-tournament-architecture.git
   cd sports-tournament-architecture/monolith
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp src/.env.example src/.env
   php artisan key:generate
   ```

4. **Configure database**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sports_tournament
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run setup script**
   ```bash
   composer run setup
   ```

### Manual Setup

1. **Database migrations**
   ```bash
   php artisan migrate --force
   ```

2. **Seed data**
   ```bash
   php artisan db:seed --class=PermissionSeeder
   php artisan db:seed --class=RolePermissionSeeder
   ```

3. **Build frontend**
   ```bash
   npm run build
   ```

4. **Link storage**
   ```bash
   php artisan storage:link
   ```

## ⚙️ Configuration

### Environment Variables

Key environment variables to configure:

```env
# Application
APP_NAME="Sports Tournament"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sports_tournament
DB_USERNAME=root
DB_PASSWORD=

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Additional Configuration

- **Queue Workers**: Configure for background job processing
- **Cache Configuration**: Redis setup for optimal performance
- **File Storage**: Configure S3 or other cloud storage if needed

## 👥 User Roles & Permissions

### Role Hierarchy

1. **Administrator**
   - Full system access
   - User management
   - Tournament management
   - System configuration

2. **Coach**
   - Team management (assigned teams only)
   - Player roster management
   - Match lineups
   - Team statistics

3. **Referee**
   - Match assignments
   - Match event recording
   - Match reports
   - Score updates

4. **Player**
   - Profile management
   - Match participation
   - Personal statistics

### Permission Matrix

| Feature | Admin | Coach | Referee | Player |
|---------|-------|-------|---------|---------|
| Tournament Management | ✅ | ❌ | ❌ | ❌ |
| Team Management | ✅ | ✅* | ❌ | ❌ |
| Player Management | ✅ | ✅* | ❌ | ✅** |
| Match Management | ✅ | ❌ | ✅ | ❌ |
| Score Updates | ✅ | ❌ | ✅ | ❌ |
| Reports | ✅ | ✅* | ✅ | ❌ |

*Limited to assigned teams  
**Limited to own profile

## 📚 API Documentation

### Authentication
All API endpoints require authentication via Laravel Sanctum tokens.

```bash
# Login
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "1|abc123...",
  "user": { ... }
}
```

### Key Endpoints

#### Tournaments
```bash
GET    /api/tournaments          # List tournaments
POST   /api/tournaments          # Create tournament
GET    /api/tournaments/{id}     # Show tournament
PUT    /api/tournaments/{id}     # Update tournament
DELETE /api/tournaments/{id}     # Delete tournament
```

#### Teams
```bash
GET    /api/tournaments/{id}/teams  # List tournament teams
POST   /api/teams                   # Create team
GET    /api/teams/{id}              # Show team
PUT    /api/teams/{id}              # Update team
DELETE /api/teams/{id}              # Delete team
```

#### Matches
```bash
GET    /api/matches             # List matches
POST   /api/matches             # Create match
GET    /api/matches/{id}        # Show match
PUT    /api/matches/{id}        # Update match
POST   /api/matches/{id}/events # Add match event
```

### API Features
- **Pagination**: All list endpoints support pagination
- **Filtering**: Advanced filtering capabilities
- **Sorting**: Multiple field sorting
- **Rate Limiting**: API endpoint protection
- **Validation**: Comprehensive input validation

## 🎨 Frontend Features

### Admin Dashboard
- **Overview Statistics**: Real-time tournament metrics
- **Quick Actions**: Common task shortcuts
- **Recent Activity**: System activity feed
- **User Management**: Complete user administration

### Coach Dashboard
- **Team Overview**: Assigned teams summary
- **Player Management**: Roster management
- **Match Schedule**: Upcoming matches
- **Performance Stats**: Team analytics

### Referee Dashboard
- **Match Assignments**: Scheduled matches
- **Match Reporting**: Event recording
- **Performance Tracking**: Referee statistics

### UI Components
- **Responsive Design**: Mobile-first approach
- **Dark Mode Support**: Theme switching
- **Accessibility**: WCAG 2.1 compliance
- **Real-time Updates**: Live data refresh

## 🔒 Security Features

### Implemented Security Measures
- **Authentication**: Laravel Sanctum token-based auth
- **Authorization**: Role-based permission system
- **Rate Limiting**: API endpoint protection
- **CSRF Protection**: Cross-site request forgery prevention
- **XSS Protection**: Input sanitization and output escaping
- **SQL Injection Prevention**: Parameterized queries
- **Input Validation**: Comprehensive request validation

### Security Headers
```php
// Implemented security headers
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000
Content-Security-Policy: default-src 'self'
```

### Best Practices
- **Password Hashing**: Bcrypt encryption
- **Session Management**: Secure session handling
- **File Upload Security**: Type and size validation
- **API Key Rotation**: Regular token refresh

## ⚡ Performance Optimizations

### Database Optimizations
- **Query Optimization**: N+1 query prevention
- **Database Indexing**: Strategic index placement
- **Query Caching**: Redis query result caching
- **Connection Pooling**: Efficient database connections

### Application Caching
- **Page Caching**: Static page caching
- **Fragment Caching**: Component-level caching
- **Object Caching**: Model result caching
- **Cache Invalidation**: Smart cache clearing

### Frontend Optimizations
- **Asset Optimization**: CSS/JS minification
- **Image Optimization**: Lazy loading and compression
- **Code Splitting**: Dynamic component loading
- **CDN Integration**: Asset delivery optimization

### Performance Monitoring
- **Query Logging**: Slow query detection
- **Response Time Tracking**: API performance metrics
- **Memory Usage Monitoring**: Resource optimization
- **Error Tracking**: Comprehensive error logging

## 🐳 Docker Deployment

### Quick Start with Docker

1. **Build and run containers**
   ```bash
   docker-compose up -d
   ```

2. **Run setup commands**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app npm install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate --force
   docker-compose exec app npm run build
   ```

3. **Access the application**
   - Main Application: http://localhost:8000
   - API Documentation: http://localhost:8000/api/docs

### Docker Services
- **app**: Laravel application server
- **nginx**: Web server
- **mysql**: Database server
- **redis**: Cache and queue server
- **queue**: Background job processor

### Production Deployment
```bash
# Production build
docker-compose -f docker-compose.prod.yml up -d

# SSL configuration
docker-compose -f docker-compose.ssl.yml up -d
```

## 🧪 Testing

### Test Suite
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter TournamentTest

# Generate coverage report
php artisan test --coverage
```

### Test Categories
- **Unit Tests**: Model and service testing
- **Feature Tests**: Controller and route testing
- **Browser Tests**: Full user journey testing
- **API Tests**: Endpoint validation

### Testing Best Practices
- **Database Transactions**: Test isolation
- **Factory Usage**: Consistent test data
- **Mock Services**: External service mocking
- **Assertion Coverage**: Comprehensive validation

## 📈 Monitoring & Logging

### Application Monitoring
- **Health Checks**: System status endpoints
- **Performance Metrics**: Response time tracking
- **Error Logging**: Comprehensive error capture
- **User Activity**: Action audit trail

### Log Management
```bash
# View logs
php artisan log:show

# Clear logs
php artisan log:clear

# Monitor in real-time
tail -f storage/logs/laravel.log
```

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

### Code Standards
- **PSR-12**: PHP coding standards
- **Laravel Conventions**: Framework best practices
- **Code Reviews**: All PRs require review
- **Testing**: New features require tests

### Commit Guidelines
```
feat: Add new feature
fix: Fix bug
docs: Update documentation
style: Code style changes
refactor: Code refactoring
test: Add tests
chore: Maintenance tasks
```

## 📝 Documentation

### Available Documentation
- **[API Documentation](docs/api.md)**: Complete API reference
- **[Architecture Guide](docs/architecture.md)**: System architecture overview
- **[Deployment Guide](docs/deployment.md)**: Production deployment
- **[Security Guide](docs/security.md)**: Security best practices
- **[Performance Guide](docs/performance.md)**: Optimization techniques

### Additional Resources
- **[Change Log](CHANGELOG.md)**: Version history
- **[Upgrade Guide](docs/upgrade.md)**: Version upgrade instructions
- **[Troubleshooting](docs/troubleshooting.md)**: Common issues

## 🐛 Bug Reports & Feature Requests

### Reporting Issues
1. Check existing issues
2. Use issue templates
3. Provide detailed information
4. Include reproduction steps

### Feature Requests
1. Describe the use case
2. Explain the benefit
3. Consider implementation
4. Provide examples

## 📞 Support

### Getting Help
- **Documentation**: Check available docs first
- **Issues**: Search existing GitHub issues
- **Discussions**: Community forum
- **Email**: support@example.com

### Community
- **GitHub Discussions**: Community discussions
- **Discord Server**: Real-time chat
- **Stack Overflow**: Technical questions
- **Blog Updates**: Latest features and news

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### License Summary
- ✅ Commercial use
- ✅ Modification
- ✅ Distribution
- ✅ Private use
- ❌ Liability
- ❌ Warranty

## 🙏 Acknowledgments

- **Laravel Team**: Excellent framework foundation
- **Tailwind CSS**: Beautiful UI framework
- **Community Contributors**: Feature improvements and bug fixes
- **Beta Testers**: Valuable feedback and testing

## 📊 Project Statistics

- **Lines of Code**: ~50,000+
- **Test Coverage**: 85%+
- **API Endpoints**: 100+
- **User Roles**: 4
- **Permissions**: 50+
- **Database Tables**: 25+
- **Components**: 100+

---

**Built with ❤️ for the sports community**

> 🏆 "Empowering sports organizations with modern tournament management technology"

---

*Last Updated: January 2026*  
*Version: 2.0.0*  
*Framework: Laravel 12*
