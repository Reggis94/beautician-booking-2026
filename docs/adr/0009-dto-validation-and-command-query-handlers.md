# ADR 0009: DTO Validation and Command/Query Handlers

## Context
Some write controllers were validating request data inline and calling DAOs directly. This mixes HTTP concerns with validation and application actions, and makes it harder to standardize validation and reuse application logic.

## Decision
For endpoints that accept input, controllers must:
- Map request input into a dedicated DTO that owns validation rules via Symfony Validator constraints.
- Create a command (for writes) or a query (for reads) from the validated DTO and pass it to a handler that executes the action.

## Consequences
- Validation rules live in DTOs instead of controllers.
- Controllers stay thin and consistent across domains.
- Handlers become the single entry point for write and read operations, easing reuse and testing.
