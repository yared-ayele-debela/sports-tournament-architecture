# ADR-002: Why Laravel Framework

## Status
Accepted

## Context
We needed to choose a backend framework for implementing the microservices. The framework should support:
- RESTful API development
- Database interactions
- Authentication and authorization
- Event publishing and consumption
- Queue management
- Service-to-service communication

## Decision
We have chosen Laravel (version 11/12) as the framework for all microservices.

## Rationale

### Primary Reason
**Team Experience**: The development team has existing experience with PHP and Laravel. This prior knowledge significantly reduces the learning curve and development time, allowing us to focus on architecture and business logic rather than learning a new framework.

### Technical Advantages
1. **Rapid Development**: Laravel's built-in features (Eloquent ORM, migrations, routing) speed up development
2. **RESTful API Support**: Excellent support for building REST APIs with minimal boilerplate
3. **Authentication**: Laravel Passport provides OAuth2 token-based authentication out of the box
4. **Database Abstraction**: Eloquent ORM makes database operations simple and consistent
5. **Queue System**: Built-in queue system works seamlessly with Redis
6. **Event System**: Native event system that integrates well with Redis Pub/Sub
7. **Validation**: Robust validation system for request handling
8. **Middleware**: Flexible middleware system for cross-cutting concerns (authentication, CORS, rate limiting)
9. **Testing**: Built-in testing framework (PHPUnit) for unit and feature tests
10. **Documentation**: Excellent documentation and large community support

### Framework Features Used
- **Laravel Passport**: For OAuth2 token-based authentication
- **Eloquent ORM**: For database interactions
- **Migrations**: For database schema management
- **Redis Integration**: For caching and event queue
- **Middleware**: For authentication, CORS, rate limiting
- **Service Providers**: For dependency injection and service configuration
- **Artisan Commands**: For database migrations, queue workers, etc.

## Consequences

### Positive
- ✅ Faster development due to team familiarity
- ✅ Consistent codebase across all services
- ✅ Rich ecosystem of packages and libraries
- ✅ Built-in features reduce boilerplate code
- ✅ Good documentation and community support
- ✅ Easy to onboard new developers familiar with Laravel
- ✅ Mature framework with proven stability

### Negative
- ⚠️ PHP performance may be slower than compiled languages (Go, Java)
- ⚠️ All services use the same technology (less technology diversity)
- ⚠️ Laravel's overhead may be more than needed for simple services
- ⚠️ Memory usage can be higher than lightweight frameworks

## Notes
- This decision prioritizes team productivity and project delivery over technology exploration
- Laravel's ecosystem (Passport, Redis integration, Eloquent) provides all necessary features for microservices
- The framework choice does not prevent future migration of individual services if needed
- Performance is acceptable for the expected load of a sports tournament management system
- All services benefit from consistent framework, making code sharing and maintenance easier
