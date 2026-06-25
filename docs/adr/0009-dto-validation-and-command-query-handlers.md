# ADR 0009: Automatic Request Mapping to Commands and Queries

## Context
Some write controllers were validating request data inline and calling DAOs directly. A previous version of this ADR required controllers to map request data into dedicated DTOs before creating commands or queries.

That extra DTO layer made simple command and query creation more verbose, duplicated input shapes, and created more files to keep synchronized. Symfony can map request payloads and query strings directly into typed command and query objects while applying Symfony Validator constraints on those objects.

## Decision
For endpoints that accept input, prefer automatic request mapping directly to commands and queries:
- Use Symfony request mapping attributes such as `MapRequestPayload` and `MapQueryString` when they fit the endpoint.
- Put request validation constraints on the mapped command or query object.
- Pass the validated command or query to a handler that executes the action.
- Add a separate DTO only when the external request shape genuinely differs from the application command/query shape or when it removes meaningful complexity.

## Consequences
- Validation rules live on command and query objects instead of controllers or mandatory DTOs.
- Controllers stay thin and consistent across domains.
- Handlers become the single entry point for write and read operations, easing reuse and testing.
- Simple endpoints need fewer intermediary classes and less manual request parsing.
