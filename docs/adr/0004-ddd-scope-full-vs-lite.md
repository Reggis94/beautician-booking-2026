# ADR 0004: DDD Scope (Full vs DDD-lite)

## Context
The system is transitioning from a conventional layered structure to Domain-Driven Design (DDD). Not all domains carry the same level of business invariants or complexity.

## Decision
Use full DDD only where invariants are heavy (entities, repositories, explicit domain layer). For simpler domains, use DDD-lite: keep domain logic in the `Application` layer and rely on DAOs or application-level repository contracts instead of a full domain model.

Current scope:
- Full DDD: `Availability`, `Services`
- DDD-lite: `ProfilePro`, `Booking`, `Store`, `ProAuth`

`Services` moved to full DDD when service and category versioning, publication and images were introduced
(ADR 0022). Service category behavior is part of the `Services` domain; see ADR 0012.

## Consequences
- Domain modeling depth varies by domain based on complexity.
- DDD-lite domains should avoid introducing full repositories/entities unless invariants grow.
- The scope list must be updated if domains change.
