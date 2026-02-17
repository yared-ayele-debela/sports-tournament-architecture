# ADR-004: Database per Service Pattern

## Status
Accepted

## Context
In a microservices architecture, we need to decide how to handle data storage. Each service manages its own domain data:
- Auth Service: Users, roles, permissions
- Tournament Service: Tournaments, sports, venues
- Team Service: Teams, players
- Match Service: Matches, match events, schedules
- Results Service: Match results, standings, statistics

## Decision
We have chosen the "Database per Service" pattern, where each microservice has its own dedicated database.

### Database Allocation
- **Auth Service** → `auth_db` (MySQL)
- **Tournament Service** → `tournament_db` (MySQL)
- **Team Service** → `team_db` (MySQL)
- **Match Service** → `match_db` (MySQL)
- **Results Service** → `results_db` (MySQL)

## Rationale

### Advantages
1. **Data Isolation**: Each service owns and controls its data, preventing unauthorized access
2. **Independent Scaling**: Databases can be scaled independently based on service load
3. **Technology Flexibility**: Each service could use a different database type if needed (though we use MySQL for all)
4. **Schema Independence**: Services can evolve their schemas without affecting others
5. **Fault Isolation**: Database failures in one service don't affect others
6. **Clear Ownership**: Clear boundaries of data ownership and responsibility
7. **Deployment Independence**: Database migrations can be run independently per service
8. **Performance**: No cross-service database queries, reducing latency
9. **Security**: Each service only has access to its own database

### Microservices Best Practice
This pattern is a core principle of microservices architecture, ensuring true service independence.

## Consequences

### Positive
- ✅ True service independence and autonomy
- ✅ No shared database bottlenecks
- ✅ Services can choose optimal database for their needs
- ✅ Easier to understand data boundaries
- ✅ Better security (services can't access other service data directly)
- ✅ Independent backup and recovery strategies

### Negative
- ⚠️ Data consistency challenges (solved with eventual consistency)
- ⚠️ No ACID transactions across services
- ⚠️ More databases to manage and maintain
- ⚠️ Data duplication (e.g., tournament data cached in multiple services)
- ⚠️ Complex queries spanning multiple services require service calls
- ⚠️ More infrastructure overhead (5 databases instead of 1)

## Data Consistency Strategy

### Eventual Consistency
We accept eventual consistency and use event-driven updates:
- When Tournament Service creates a tournament, it publishes `tournament.created` event
- Team Service and Match Service consume the event and cache tournament data
- If a service needs fresh data, it can make an HTTP call to the owning service

### Data Duplication
We intentionally duplicate some data for performance:
- Tournament data is cached in Team Service and Match Service
- This allows fast lookups without service calls
- Cache is invalidated when events indicate data changes

### Cross-Service Queries
When we need data from multiple services:
- Make parallel HTTP calls to multiple services
- Aggregate results in the requesting service
- Use caching to reduce service calls

## Alternatives Considered

### Shared Database
- **Rejected because**: 
  - Violates microservices principles
  - Creates tight coupling between services
  - Single point of failure
  - Schema changes affect multiple services
  - Security concerns (services can access all data)

### Database per Service with Read Replicas
- **Not chosen because**: 
  - Adds complexity for current project scope
  - Can be added later if needed for scaling

### Event Sourcing
- **Not chosen because**: 
  - More complex to implement
  - Requires significant refactoring
  - Overkill for current requirements
  - Team has less experience with event sourcing

### CQRS (Command Query Responsibility Segregation)
- **Not chosen because**: 
  - Adds architectural complexity
  - Requires separate read/write models
  - Current read/write patterns are simple enough

## Implementation Details

### Database Technology
- **Choice**: MySQL 8.0 for all services
- **Reason**: Team familiarity, Laravel excellent support, proven reliability
- **Future**: Services could migrate to PostgreSQL, MongoDB, etc. if needed

### Migration Strategy
- Each service manages its own migrations
- Migrations run independently during deployment
- No cross-service migration dependencies

### Backup Strategy
- Each database backed up independently
- Service-specific backup schedules based on data criticality
- Point-in-time recovery per service

## Notes
- This pattern is essential for true microservices architecture
- The trade-off of eventual consistency is acceptable for this domain
- Data duplication is intentional for performance optimization
- Services communicate via APIs, not direct database access
- This pattern demonstrates understanding of distributed system challenges
- The pattern aligns with course requirements for microservices architecture
