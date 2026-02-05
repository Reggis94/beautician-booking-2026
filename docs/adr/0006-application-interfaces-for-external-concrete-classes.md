# ADR 0006: Application Interfaces for External Concrete Classes

## Context
Concrete classes outside the Application layer (adapters, infrastructure, delivery) are used by application services. Without a corresponding application-level interface, the application layer becomes tightly coupled to specific implementations, making substitution and testing harder.

## Decision
Any concrete class outside the Application layer that is used by the Application layer must have a corresponding interface defined in the Application layer. Application code depends on the interface, and the concrete class implements it.

## Consequences
- New external concrete classes must come with an application-level interface.
- Dependencies in application services should be typed to the interface, not the implementation.
- Swapping adapters or providers requires changing only wiring, not application logic.
