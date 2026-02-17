# ADR-001: Why Microservices Architecture

## Status
Accepted

## Context
We needed to design a distributed sports tournament management system that can handle multiple concerns: user authentication, tournament management, team management, match scheduling, and results tracking. The system needs to be scalable, maintainable, and allow independent development and deployment of different components.

## Decision
We have chosen to implement a microservices architecture with 5 independent services:
- Auth Service (Port 8001)
- Tournament Service (Port 8002)
- Team Service (Port 8003)
- Match Service (Port 8004)
- Results Service (Port 8005)

Each service is independently deployable, has its own database, and communicates with other services via HTTP/REST (synchronous) and Redis Pub/Sub (asynchronous).

## Rationale

### Advantages
1. **Independent Development**: Different teams can work on different services simultaneously without conflicts
2. **Technology Flexibility**: Each service can use different technologies if needed (though we're using Laravel for all)
3. **Scalability**: Services can be scaled independently based on load (e.g., Match Service during live events)
4. **Fault Isolation**: If one service fails, others continue to operate
5. **Deployment Independence**: Services can be deployed separately without affecting others
6. **Clear Boundaries**: Each service has a well-defined responsibility and domain
7. **Team Autonomy**: Teams can make decisions about their service without affecting others

### Disadvantages
1. **Increased Complexity**: More services to manage, deploy, and monitor
2. **Network Latency**: Service-to-service calls add network overhead
3. **Data Consistency**: Maintaining consistency across services requires careful design
4. **Distributed Transactions**: No single database transaction across services
5. **Testing Complexity**: Integration testing becomes more complex
6. **Operational Overhead**: More infrastructure to manage (containers, databases, networks)

## Consequences

### Positive
- ✅ Services can be developed and deployed independently
- ✅ Better fault tolerance (one service failure doesn't bring down entire system)
- ✅ Easier to scale individual services based on demand
- ✅ Clear separation of concerns
- ✅ Easier to understand and maintain individual services
- ✅ Allows for future technology migration (e.g., rewrite one service in a different language)

### Negative
- ⚠️ More complex deployment and orchestration (Docker Compose helps)
- ⚠️ Service-to-service communication overhead
- ⚠️ Eventual consistency challenges (solved with event-driven architecture)
- ⚠️ More monitoring and logging infrastructure needed
- ⚠️ Network failures can cause service unavailability

## Alternatives Considered

### Monolithic Architecture
- **Rejected because**: Would make it difficult to scale individual components, harder to maintain, and all developers would work on the same codebase causing conflicts

### Service-Oriented Architecture (SOA)
- **Rejected because**: SOA typically involves heavier middleware and more coupling between services. Microservices provide better independence.

### Serverless Architecture
- **Rejected because**: Would require significant refactoring, vendor lock-in concerns, and less control over infrastructure. Team has more experience with traditional deployment.

## Notes
- This decision aligns with the course requirement to demonstrate distributed system architecture
- The microservices pattern is well-suited for a sports tournament system where different domains (tournaments, teams, matches, results) have distinct responsibilities
- Event-driven communication (Redis Pub/Sub) helps maintain loose coupling between services
