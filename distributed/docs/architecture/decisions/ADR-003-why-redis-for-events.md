# ADR-003: Why Redis for Events

## Status
Accepted

## Context
We needed a mechanism for asynchronous communication between microservices to maintain loose coupling. Services need to:
- Publish events when domain events occur (e.g., tournament created, match completed)
- Subscribe to events from other services
- Handle events asynchronously without blocking the main request flow
- Support event-driven updates (e.g., cache invalidation, standings recalculation)

## Decision
We have chosen Redis Pub/Sub as the event messaging system for asynchronous communication between services.

## Rationale

### Advantages
1. **Simplicity**: Redis is straightforward to set up and use, especially with Laravel's built-in Redis support
2. **Performance**: Redis is in-memory, providing very low latency for message delivery
3. **Laravel Integration**: Laravel has excellent Redis support out of the box
4. **Lightweight**: Redis is lightweight compared to full message brokers (RabbitMQ, Kafka)
5. **Dual Purpose**: Redis can be used for both caching and event queue, reducing infrastructure complexity
6. **No Additional Dependencies**: Already using Redis for caching, so no new infrastructure needed
7. **Pub/Sub Pattern**: Simple publish-subscribe pattern fits our event-driven needs
8. **Fast Setup**: Quick to configure and deploy in Docker environment

### Event Flow
- Services publish events to Redis channels (e.g., `events` channel)
- Other services subscribe to relevant channels
- Events are delivered in real-time to all subscribers
- Services process events asynchronously via queue workers

### Event Types Used
- `tournament.created`, `tournament.updated`, `tournament.status.changed`
- `match.completed`, `match.scheduled`
- `standings.updated`, `statistics.updated`
- `user.registered`, `user.logged.in`

## Consequences

### Positive
- ✅ Simple to implement and maintain
- ✅ Low latency for event delivery
- ✅ No additional infrastructure (reuses Redis)
- ✅ Good Laravel integration
- ✅ Fast development and deployment
- ✅ Suitable for the scale of this application

### Negative
- ⚠️ No message persistence by default (messages lost if subscriber is down)
- ⚠️ No guaranteed delivery (if service crashes, events may be lost)
- ⚠️ Limited scalability compared to dedicated message brokers
- ⚠️ No message ordering guarantees across multiple channels
- ⚠️ No built-in retry mechanism (we implement our own)
- ⚠️ Memory-based (limited by available RAM)

## Mitigations

### Message Persistence
- We implement event handlers that can recover from failures
- Critical events are also stored in databases for audit trails
- Queue workers process events with retry logic

### Guaranteed Delivery
- We use priority queues (high/normal/low) for critical events
- Event handlers include retry mechanisms
- Services can re-request data if events are missed

### Scalability
- For current scale (academic project), Redis Pub/Sub is sufficient
- If scaling becomes an issue, we can migrate to RabbitMQ or Kafka

## Alternatives Considered

### RabbitMQ
- **Rejected because**: More complex setup, requires additional infrastructure, and Redis is sufficient for our needs. Team has less experience with RabbitMQ.

### Apache Kafka
- **Rejected because**: Overkill for this project size, more complex to set up and operate, and requires significant infrastructure.

### Database-based Event Log
- **Rejected because**: Higher latency, database becomes a bottleneck, and adds complexity to database schema.

### Direct HTTP Webhooks
- **Rejected because**: Tight coupling between services, synchronous blocking calls, and harder to handle failures.

### AWS SQS / Azure Service Bus
- **Rejected because**: Vendor lock-in, requires cloud infrastructure, and adds cost/complexity.

## Notes
- Redis Pub/Sub is suitable for the academic project scope
- The system can be migrated to a more robust message broker (RabbitMQ/Kafka) if needed in production
- We implement custom retry and error handling to mitigate Redis limitations
- Redis also serves as cache, making it a cost-effective choice
- Event payloads are kept small to minimize memory usage
