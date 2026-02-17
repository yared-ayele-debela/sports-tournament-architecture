# Architecture Decision Records (ADRs)

This directory contains Architecture Decision Records (ADRs) that document important architectural decisions made for this project.

## What are ADRs?

Architecture Decision Records are documents that capture important architectural decisions along with their context and consequences. They help:
- Document why certain decisions were made
- Provide context for future developers
- Track the evolution of the architecture
- Justify design choices

## ADR Index

| ADR | Title | Status | Description |
|-----|-------|--------|-------------|
| [ADR-001](./ADR-001-why-microservices-architecture.md) | Why Microservices Architecture | Accepted | Decision to use microservices architecture instead of monolithic |
| [ADR-002](./ADR-002-why-laravel-framework.md) | Why Laravel Framework | Accepted | Decision to use Laravel for all microservices |
| [ADR-003](./ADR-003-why-redis-for-events.md) | Why Redis for Events | Accepted | Decision to use Redis Pub/Sub for event-driven communication |
| [ADR-004](./ADR-004-database-per-service-pattern.md) | Database per Service Pattern | Accepted | Decision to use separate database for each microservice |

## ADR Format

Each ADR follows this structure:
- **Status**: Accepted, Proposed, Deprecated, or Superseded
- **Context**: The situation and requirements that led to the decision
- **Decision**: What was decided
- **Rationale**: Why this decision was made
- **Consequences**: Positive and negative impacts
- **Alternatives Considered**: Other options that were evaluated

## How to Add a New ADR

1. Create a new file: `ADR-XXX-short-description.md`
2. Follow the template structure
3. Update this README with the new ADR
4. Use sequential numbering (ADR-005, ADR-006, etc.)

## Related Documentation

- [Architecture Overview](../../README.md#architecture)
- [System Architecture Diagram](../../System%20Architecture%20Diagram/)
- [Sequence Diagrams](../../Sequence%20Diagrams/)
