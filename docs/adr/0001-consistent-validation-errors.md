# ADR 0001: Consistent Validation Error Format

## Context
Validation happens both in HTTP controllers (via Symfony validator) and in command handlers (throwing `\InvalidArgumentException`). API consumers should not have to handle two different error shapes for the same kind of validation failure.

## Decision
Return validation errors as an array of message strings, matching the format used when a command handler throws and is caught in the controller.

## Consequences
- Controllers should convert `ConstraintViolationList` instances to an array of messages before responding.
- When adding new validations in command handlers, ensure thrown messages map cleanly into the same array structure.
- API clients can rely on a single error format: `{"errors": ["message 1", "message 2"]}`.
